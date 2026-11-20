<?php

namespace App\Services\Production;

use App\Enums\FinishingJobStatus;
use App\Enums\FinishingType;
use App\Enums\ItemStatus;
use App\Enums\PackingStatus;
use App\Enums\Priority;
use App\Enums\QcResult;
use App\Enums\RedoStage;
use App\Enums\ReprintStatus;
use App\Enums\ReworkStatus;
use App\Models\FinishingJob;
use App\Models\OrderItem;
use App\Models\Packing;
use App\Models\QcRecord;
use App\Models\ReprintRequest;
use App\Models\Rework;
use App\Models\User;
use App\Services\Order\OrderService;
use App\Services\System\AuditLogService;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Business logic finishing / QC / rework / packing (MOD-05, BR-11, BR-12, BR-17).
 */
class FinishingService
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Item yang siap atau sedang menjalani finishing, urut prioritas & deadline.
     *
     * @return Collection<int, OrderItem>
     */
    public function queue(): Collection
    {
        return OrderItem::query()
            ->with([
                'order.customer',
                'finishingJobs.assignee',
                'finishingJobs.qcRecords',
                'finishingJobs.reworks',
            ])
            ->whereIn('status', [
                ItemStatus::Printed,
                ItemStatus::Finishing,
                ItemStatus::Qc,
                ItemStatus::Rework,
                ItemStatus::Packing,
            ])
            ->get()
            ->sortBy([
                fn (OrderItem $a, OrderItem $b) => $this->priorityRank($a) <=> $this->priorityRank($b),
                fn (OrderItem $a, OrderItem $b) => ($a->deadline_at?->timestamp ?? PHP_INT_MAX) <=> ($b->deadline_at?->timestamp ?? PHP_INT_MAX),
                fn (OrderItem $a, OrderItem $b) => $a->id <=> $b->id,
            ])->values();
    }

    /**
     * Masukkan item hasil cetak ke antrean finishing (AC-05-1, BR-12).
     *
     * @throws ValidationException
     */
    public function intake(OrderItem $item, User $actor, ?string $type = null): FinishingJob
    {
        if ($item->status !== ItemStatus::Printed) {
            throw ValidationException::withMessages([
                'item' => 'Hanya item dengan status Sudah Cetak yang boleh masuk finishing (BR-12).',
            ]);
        }

        return DB::transaction(function () use ($item, $actor, $type): FinishingJob {
            $locked = OrderItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ItemStatus::Printed) {
                throw ValidationException::withMessages([
                    'item' => 'Hanya item dengan status Sudah Cetak yang boleh masuk finishing (BR-12).',
                ]);
            }

            $job = FinishingJob::query()->firstOrCreate(
                ['order_item_id' => $locked->id],
                [
                    'type' => $this->resolveType($type, $locked)?->value,
                    'status' => FinishingJobStatus::Queued,
                ],
            );

            $this->orders->changeItemStatus(
                $locked,
                ItemStatus::Finishing,
                $actor,
                'Item masuk antrean finishing.',
            );

            $this->audit->record(
                action: 'finishing.intake',
                actor: $actor,
                modelType: FinishingJob::class,
                modelId: $job->id,
                after: $this->jobSnapshot($job->fresh()),
            );

            return $job->fresh(['orderItem.order.customer', 'assignee']);
        });
    }

    /**
     * Admin Produksi membagikan pekerjaan finishing (MOD-05 fitur 2).
     *
     * @throws ValidationException
     */
    public function assign(FinishingJob $job, User $operator, User $actor): FinishingJob
    {
        return DB::transaction(function () use ($job, $operator, $actor): FinishingJob {
            $locked = FinishingJob::query()->whereKey($job->id)->lockForUpdate()->firstOrFail();

            if ($locked->assigned_to !== null) {
                throw ValidationException::withMessages([
                    'operator' => 'Pekerjaan sudah dipegang operator lain.',
                ]);
            }

            $before = $this->jobSnapshot($locked);

            $locked->update([
                'assigned_to' => $operator->id,
                'assigned_by' => $actor->id,
                'status' => FinishingJobStatus::InProgress,
                'started_at' => $locked->started_at ?? now(),
            ]);

            $this->audit->record(
                action: 'finishing.assigned',
                actor: $actor,
                modelType: FinishingJob::class,
                modelId: $locked->id,
                before: $before,
                after: $this->jobSnapshot($locked->fresh()),
            );

            return $locked->fresh(['assignee', 'assigner', 'orderItem.order.customer']);
        });
    }

    /**
     * Operator finishing mengambil pekerjaan dari antrean (self-assign).
     *
     * @throws ValidationException
     */
    public function claim(FinishingJob $job, User $actor): FinishingJob
    {
        return DB::transaction(function () use ($job, $actor): FinishingJob {
            $locked = FinishingJob::query()->whereKey($job->id)->lockForUpdate()->firstOrFail();

            if ($locked->assigned_to !== null) {
                throw ValidationException::withMessages([
                    'operator' => 'Pekerjaan sudah dipegang operator lain.',
                ]);
            }

            $before = $this->jobSnapshot($locked);

            $locked->update([
                'assigned_to' => $actor->id,
                'assigned_by' => $actor->id,
                'status' => FinishingJobStatus::InProgress,
                'started_at' => $locked->started_at ?? now(),
            ]);

            $this->audit->record(
                action: 'finishing.claimed',
                actor: $actor,
                modelType: FinishingJob::class,
                modelId: $locked->id,
                before: $before,
                after: $this->jobSnapshot($locked->fresh()),
            );

            return $locked->fresh(['assignee', 'assigner', 'orderItem.order.customer']);
        });
    }

    /**
     * Catat progres/catatan finishing (MOD-05 fitur 3).
     *
     * @param  array{progress?: int|string|null, progress_note?: string|null, started_at?: mixed, finished_at?: mixed}  $data
     */
    public function recordProgress(FinishingJob $job, array $data, User $actor): FinishingJob
    {
        return DB::transaction(function () use ($job, $data, $actor): FinishingJob {
            $locked = FinishingJob::query()->whereKey($job->id)->lockForUpdate()->firstOrFail();
            $before = $this->jobSnapshot($locked);
            $updates = [];
            $finished = false;

            if (! empty($data['started_at'])) {
                $updates['started_at'] = Carbon::parse($data['started_at']);
            }

            if (! empty($data['finished_at'])) {
                $updates['finished_at'] = Carbon::parse($data['finished_at']);
                $updates['progress'] = 100;
                $finished = true;
            }

            if (array_key_exists('progress_note', $data)) {
                $value = $data['progress_note'];
                $updates['progress_note'] = $value !== null && trim((string) $value) !== ''
                    ? trim((string) $value)
                    : null;
            }

            if ($updates !== []) {
                $updates['status'] = $finished
                    ? FinishingJobStatus::Completed
                    : FinishingJobStatus::InProgress;
                $locked->update($updates);
            }

            $this->audit->record(
                action: 'finishing.progress_recorded',
                actor: $actor,
                modelType: FinishingJob::class,
                modelId: $locked->id,
                before: $before,
                after: $this->jobSnapshot($locked->fresh()),
            );

            return $locked->fresh(['assignee', 'assigner', 'orderItem.order.customer']);
        });
    }

    /**
     * Catat hasil QC. Gagal wajib bercatatan dan langsung membuka rework
     * (AC-05-2, AC-05-3).
     *
     * @throws ValidationException
     */
    public function recordQc(
        FinishingJob $job,
        QcResult|string $result,
        ?string $notes,
        User $actor,
        RedoStage|string|null $redoStage = null,
    ): QcRecord {
        $result = $result instanceof QcResult ? $result : QcResult::from((string) $result);
        $redoStage = $redoStage instanceof RedoStage
            ? $redoStage
            : (is_string($redoStage) && $redoStage !== '' ? RedoStage::from($redoStage) : null);
        $notes = $notes === null ? '' : trim($notes);

        if ($result === QcResult::Fail && $notes === '') {
            throw ValidationException::withMessages([
                'notes' => 'Catatan wajib diisi untuk hasil QC gagal.',
            ]);
        }

        return DB::transaction(function () use ($job, $result, $notes, $actor, $redoStage): QcRecord {
            $lockedJob = FinishingJob::query()->whereKey($job->id)->lockForUpdate()->firstOrFail();
            $item = OrderItem::query()->whereKey($lockedJob->order_item_id)->lockForUpdate()->firstOrFail();

            if (! in_array($item->status, [ItemStatus::Finishing, ItemStatus::Qc, ItemStatus::Rework], true)) {
                throw ValidationException::withMessages([
                    'item' => 'QC hanya dapat dicatat pada item yang sedang proses finishing/QC/rework.',
                ]);
            }

            if ($lockedJob->status !== FinishingJobStatus::Completed) {
                $lockedJob->update([
                    'status' => FinishingJobStatus::Completed,
                    'finished_at' => $lockedJob->finished_at ?? now(),
                    'progress' => 100,
                ]);
            }

            $beforeStatus = $item->status;

            $record = QcRecord::create([
                'finishing_job_id' => $lockedJob->id,
                'result' => $result,
                'notes' => $notes !== '' ? $notes : null,
                'checked_by' => $actor->id,
                'checked_at' => now(),
            ]);

            if ($result === QcResult::Fail) {
                $rework = Rework::create([
                    'order_item_id' => $item->id,
                    'qc_record_id' => $record->id,
                    'finishing_job_id' => $lockedJob->id,
                    'reason' => $notes,
                    'redo_stage' => $redoStage ?? RedoStage::Finishing,
                    'status' => ReworkStatus::Pending,
                ]);

                $this->audit->record(
                    action: 'finishing.rework_started',
                    actor: $actor,
                    modelType: Rework::class,
                    modelId: $rework->id,
                    after: [
                        'order_item_id' => $item->id,
                        'redo_stage' => ($redoStage ?? RedoStage::Finishing)->value,
                        'reason' => $notes,
                    ],
                );

                if ($item->status !== ItemStatus::Rework) {
                    $this->orders->changeItemStatus($item, ItemStatus::Rework, $actor, 'QC gagal: '.$notes);
                }
            } elseif ($item->status !== ItemStatus::Qc) {
                $this->orders->changeItemStatus($item, ItemStatus::Qc, $actor, 'QC lulus, item siap dikemas.');
            }

            $this->audit->record(
                action: 'finishing.qc_recorded',
                actor: $actor,
                modelType: QcRecord::class,
                modelId: $record->id,
                before: ['item_status' => $beforeStatus?->value],
                after: [
                    'result' => $result->value,
                    'notes' => $record->notes,
                    'item_status' => $item->fresh()?->status?->value,
                ],
            );

            return $record->load(['checker', 'finishingJob.orderItem']);
        });
    }

    /**
     * Tandai rework sebagai dimulai (AC-05-4).
     */
    public function startRework(Rework $rework, User $actor): Rework
    {
        return DB::transaction(function () use ($rework, $actor): Rework {
            $locked = Rework::query()->whereKey($rework->id)->lockForUpdate()->firstOrFail();
            $item = OrderItem::query()->whereKey($locked->order_item_id)->lockForUpdate()->firstOrFail();

            if ($item->status !== ItemStatus::Rework) {
                $this->orders->changeItemStatus(
                    $item,
                    ItemStatus::Rework,
                    $actor,
                    'Rework dimulai: '.$locked->reason,
                );
            }

            $this->audit->record(
                action: 'finishing.rework_started',
                actor: $actor,
                modelType: Rework::class,
                modelId: $locked->id,
                after: ['status' => $locked->status?->value, 'item_status' => ItemStatus::Rework->value],
            );

            return $locked->fresh(['doer', 'doneBy', 'orderItem', 'qcRecord']);
        });
    }

    /**
     * Selesaikan rework agar QC dapat diulang (AC-05-4).
     *
     * @throws ValidationException
     */
    public function completeRework(Rework $rework, User $actor, ?string $note = null): Rework
    {
        return DB::transaction(function () use ($rework, $actor, $note): Rework {
            $locked = Rework::query()->whereKey($rework->id)->lockForUpdate()->firstOrFail();
            $item = OrderItem::query()->whereKey($locked->order_item_id)->lockForUpdate()->firstOrFail();

            if ($locked->status === ReworkStatus::Done) {
                throw ValidationException::withMessages([
                    'rework' => 'Rework ini sudah diselesaikan.',
                ]);
            }

            if ($item->status !== ItemStatus::Rework) {
                throw ValidationException::withMessages([
                    'rework' => 'Item tidak dalam status rework.',
                ]);
            }

            if ($locked->redo_stage === RedoStage::Cetak) {
                $approvedReprint = ReprintRequest::query()
                    ->whereHas('printJob', fn ($q) => $q->where('order_item_id', $item->id))
                    ->where('status', ReprintStatus::Approved)
                    ->exists();

                if (! $approvedReprint) {
                    throw ValidationException::withMessages([
                        'rework' => 'Rework ke tahap cetak wajib melalui cetak ulang yang disetujui (BR-11).',
                    ]);
                }
            }

            $before = ['status' => $locked->status?->value, 'item_status' => $item->status?->value];

            $locked->update([
                'status' => ReworkStatus::Done,
                'done_by' => $actor->id,
                'done_at' => now(),
            ]);

            $this->orders->changeItemStatus(
                $item,
                ItemStatus::Qc,
                $actor,
                $note !== null && trim($note) !== '' ? trim($note) : 'Rework selesai, QC diulang.',
            );

            $this->audit->record(
                action: 'finishing.rework_completed',
                actor: $actor,
                modelType: Rework::class,
                modelId: $locked->id,
                before: $before,
                after: [
                    'status' => ReworkStatus::Done->value,
                    'item_status' => ItemStatus::Qc->value,
                    'done_by' => $actor->id,
                ],
            );

            return $locked->fresh(['doer', 'doneBy', 'orderItem', 'qcRecord']);
        });
    }

    /**
     * Mulai packing setelah QC lulus (AC-05-5, BR-12).
     *
     * @throws ValidationException
     */
    public function startPacking(OrderItem $item, User $actor): Packing
    {
        return DB::transaction(function () use ($item, $actor): Packing {
            $locked = OrderItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ItemStatus::Qc) {
                throw ValidationException::withMessages([
                    'packing' => 'Packing hanya dapat dimulai setelah QC lulus (BR-12).',
                ]);
            }

            // Status `Qc` juga dipakai saat item kembali dari rework, jadi
            // pastikan hasil QC terakhir benar-benar `pass` (AC-05-5).
            $latestQc = QcRecord::query()
                ->whereHas('finishingJob', fn ($q) => $q->where('order_item_id', $locked->id))
                ->latest('checked_at')
                ->latest('id')
                ->first();

            if ($latestQc?->result !== QcResult::Pass) {
                throw ValidationException::withMessages([
                    'packing' => 'Packing hanya dapat dimulai setelah QC lulus (BR-12).',
                ]);
            }

            if (Packing::query()->where('order_item_id', $locked->id)->exists()) {
                throw ValidationException::withMessages([
                    'packing' => 'Item ini sudah memiliki data packing.',
                ]);
            }

            $packing = Packing::create([
                'order_item_id' => $locked->id,
                'packed_by' => $actor->id,
                'status' => PackingStatus::InProgress,
            ]);

            $this->orders->changeItemStatus($locked, ItemStatus::Packing, $actor, 'Packing dimulai.');

            $this->audit->record(
                action: 'finishing.packing_started',
                actor: $actor,
                modelType: Packing::class,
                modelId: $packing->id,
                after: [
                    'order_item_id' => $locked->id,
                    'status' => PackingStatus::InProgress->value,
                ],
            );

            return $packing->fresh(['packer', 'orderItem']);
        });
    }

    /**
     * Selesaikan packing (AC-05-5).
     *
     * @throws ValidationException
     */
    public function completePacking(Packing $packing, ?string $note, User $actor): Packing
    {
        return DB::transaction(function () use ($packing, $note, $actor): Packing {
            $locked = Packing::query()->whereKey($packing->id)->lockForUpdate()->firstOrFail();
            $item = OrderItem::query()->whereKey($locked->order_item_id)->lockForUpdate()->firstOrFail();

            if ($locked->status === PackingStatus::Packed) {
                throw ValidationException::withMessages([
                    'packing' => 'Packing ini sudah diselesaikan.',
                ]);
            }

            if ($item->status !== ItemStatus::Packing) {
                throw ValidationException::withMessages([
                    'packing' => 'Item tidak dalam status packing.',
                ]);
            }

            $before = ['status' => $locked->status?->value, 'item_status' => $item->status?->value];

            $locked->update([
                'status' => PackingStatus::Packed,
                'packed_by' => $actor->id,
                'packed_at' => now(),
                'note' => $note !== null && trim($note) !== '' ? trim($note) : null,
            ]);

            $this->orders->changeItemStatus($item, ItemStatus::Packed, $actor, 'Item selesai dikemas.');

            $this->audit->record(
                action: 'finishing.packed',
                actor: $actor,
                modelType: Packing::class,
                modelId: $locked->id,
                before: $before,
                after: [
                    'status' => PackingStatus::Packed->value,
                    'item_status' => ItemStatus::Packed->value,
                ],
            );

            return $locked->fresh(['packer', 'orderItem']);
        });
    }

    /**
     * Urutan kejadian finishing → QC → rework → packing beserta pelakunya
     * (AC-05-6).
     *
     * @return Collection<int, array{at: mixed, phase: string, label: string, actor: string, note: string|null}>
     */
    public function history(OrderItem $item): Collection
    {
        $item->loadMissing([
            'statusHistories.changedBy',
            'finishingJobs.assignee',
            'finishingJobs.qcRecords.checker',
            'finishingJobs.reworks.qcRecord.checker',
            'finishingJobs.reworks.doneBy',
            'packings.packer',
        ]);

        $events = collect();
        $seq = 0;

        foreach ($item->statusHistories as $history) {
            if ($history->to_status !== ItemStatus::Finishing) {
                continue;
            }

            $events->push([
                'at' => $history->created_at,
                'phase' => 'finishing',
                'label' => 'Finishing: '.$history->to_status->label(),
                'actor' => $history->changedBy?->name ?? '-',
                'note' => $history->note,
                '_seq' => $seq++,
            ]);
        }

        foreach ($item->finishingJobs as $job) {
            if ($job->finished_at !== null) {
                $events->push([
                    'at' => $job->finished_at,
                    'phase' => 'finishing',
                    'label' => 'Finishing: '.$job->status?->label(),
                    'actor' => $job->assignee?->name ?? '-',
                    'note' => $job->progress_note,
                    '_seq' => $seq++,
                ]);
            }
        }

        foreach ($item->finishingJobs as $job) {
            foreach ($job->qcRecords as $qc) {
                $events->push([
                    'at' => $qc->checked_at,
                    'phase' => 'qc',
                    'label' => 'QC: '.$qc->result?->label(),
                    'actor' => $qc->checker?->name ?? '-',
                    'note' => $qc->notes,
                    '_seq' => $seq++,
                ]);
            }
        }

        foreach ($item->finishingJobs as $job) {
            foreach ($job->reworks as $rework) {
                $events->push([
                    'at' => $rework->created_at,
                    'phase' => 'rework',
                    'label' => 'Rework: '.$rework->redo_stage?->label(),
                    'actor' => $rework->qcRecord?->checker?->name ?? '-',
                    'note' => $rework->reason,
                    '_seq' => $seq++,
                ]);

                if ($rework->done_at !== null) {
                    $events->push([
                        'at' => $rework->done_at,
                        'phase' => 'rework',
                        'label' => 'Rework: Selesai',
                        'actor' => $rework->doneBy?->name ?? '-',
                        'note' => null,
                        '_seq' => $seq++,
                    ]);
                }
            }
        }

        foreach ($item->packings as $packing) {
            $events->push([
                'at' => $packing->packed_at ?? $packing->created_at,
                'phase' => 'packing',
                'label' => 'Packing: '.$packing->status?->label(),
                'actor' => $packing->packer?->name ?? '-',
                'note' => $packing->note,
                '_seq' => $seq++,
            ]);
        }

        return $events
            ->sortBy('_seq')
            ->map(fn (array $event): array => Arr::except($event, ['_seq']))
            ->values();
    }

    private function resolveType(?string $type, OrderItem $item): ?FinishingType
    {
        if ($type !== null && $type !== '') {
            return FinishingType::tryFrom($type);
        }

        $raw = $item->specification['jenis_finishing'] ?? null;
        if (is_array($raw)) {
            $raw = reset($raw);
        }

        return is_string($raw) && $raw !== '' ? FinishingType::tryFrom($raw) : null;
    }

    private function priorityRank(OrderItem $item): int
    {
        return match ($item->priority) {
            Priority::Urgent => 0,
            Priority::H1 => 1,
            Priority::Normal => 2,
            default => 3,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function jobSnapshot(FinishingJob $job): array
    {
        return [
            'id' => $job->id,
            'order_item_id' => $job->order_item_id,
            'type' => $job->type?->value,
            'status' => $job->status?->value,
            'assigned_to' => $job->assigned_to,
            'assigned_by' => $job->assigned_by,
            'progress' => $job->progress,
            'progress_note' => $job->progress_note,
            'started_at' => $job->started_at?->format('Y-m-d H:i:s'),
            'finished_at' => $job->finished_at?->format('Y-m-d H:i:s'),
        ];
    }
}
