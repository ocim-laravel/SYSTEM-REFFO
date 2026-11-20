<?php

namespace App\Services\Production;

use App\Enums\ItemStatus;
use App\Enums\PrintJobStatus;
use App\Enums\PrintQueueClass;
use App\Enums\Priority;
use App\Enums\ReprintStatus;
use App\Models\OrderItem;
use App\Models\PrintJob;
use App\Models\PrintJobOperator;
use App\Models\ReprintRequest;
use App\Models\User;
use App\Services\Order\OrderService;
use App\Services\System\AuditLogService;
use App\Services\System\WorkCalendarService;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Business logic produksi/cetak (MOD-04, BR-06..BR-11, BR-17).
 */
class ProductionService
{
    public function __construct(
        private readonly WorkCalendarService $calendar,
        private readonly OrderService $orders,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Hitung kelas antrean (BR-06, BR-07).
     */
    public function computeQueueClass(OrderItem $item, ?Carbon $asOf = null): PrintQueueClass
    {
        if ($item->priority === Priority::Urgent) {
            return PrintQueueClass::Urgent;
        }

        if ($item->deadline_at === null) {
            return PrintQueueClass::Regular;
        }

        $now = ($asOf ?? now())->copy()->timezone(config('app.timezone'));
        $deadline = Carbon::parse($item->deadline_at)->timezone(config('app.timezone'));
        $h1Day = $this->calendar->previousWorkday($deadline)->startOfDay();

        if ($now->startOfDay()->greaterThanOrEqualTo($h1Day)) {
            return PrintQueueClass::H1;
        }

        return PrintQueueClass::Regular;
    }

    /**
     * Buat/sinkronkan print job saat item masuk antrean produksi (intake).
     * Dipanggil dalam transaksi handoff desain (AC-03-7/BR-06/BR-07).
     */
    public function createForItem(OrderItem $item): PrintJob
    {
        $queueClass = $this->computeQueueClass($item);

        /** @var PrintJob $job */
        $job = PrintJob::query()->updateOrCreate(
            ['order_item_id' => $item->id],
            [
                'queue_class' => $queueClass,
                'status' => PrintJobStatus::Queued,
                'qty_planned' => $item->qty,
            ],
        );

        return $job;
    }

    /**
     * Sinkronkan queue_class tersimpan dengan kondisi terkini.
     */
    public function syncQueueClass(PrintJob $job): PrintJob
    {
        $job->loadMissing('orderItem');
        $computed = $this->computeQueueClass($job->orderItem);

        if ($job->queue_class !== $computed) {
            $job->update(['queue_class' => $computed]);
        }

        return $job->refresh();
    }

    /**
     * Ambil print job per kelas antrean, disinkronkan (BR-06).
     *
     * @return array{urgent: Collection<int, PrintJob>, h1: Collection<int, PrintJob>, regular: Collection<int, PrintJob>}
     */
    public function queues(): array
    {
        $jobs = PrintJob::query()
            ->with([
                'orderItem.order.customer',
                'assignee',
                'operators' => fn ($q) => $q->whereNull('ended_at')->with('operator'),
            ])
            ->whereHas('orderItem', fn ($q) => $q->whereIn('status', [
                ItemStatus::QueuedProduction->value,
                ItemStatus::Printing->value,
                ItemStatus::Printed->value,
            ]))
            ->get();

        foreach ($jobs as $job) {
            $this->syncQueueClass($job);
        }

        $jobs = $jobs->sortBy([
            fn (PrintJob $a, PrintJob $b) => $this->priorityRank($a->orderItem?->priority) <=> $this->priorityRank($b->orderItem?->priority),
            fn (PrintJob $a, PrintJob $b) => ($a->orderItem?->deadline_at?->timestamp ?? PHP_INT_MAX) <=> ($b->orderItem?->deadline_at?->timestamp ?? PHP_INT_MAX),
            fn (PrintJob $a, PrintJob $b) => $a->id <=> $b->id,
        ])->values();

        return [
            'urgent' => $jobs->where('queue_class', PrintQueueClass::Urgent)->values(),
            'h1' => $jobs->where('queue_class', PrintQueueClass::H1)->values(),
            'regular' => $jobs->where('queue_class', PrintQueueClass::Regular)->values(),
        ];
    }

    /**
     * Pembagian pekerjaan oleh Admin Produksi/kepala bagian (BR-08).
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function assign(PrintJob $job, User $operator, User $actor): PrintJob
    {
        if (! $actor->can('assign', $job)) {
            throw new AuthorizationException('Anda tidak berwenang membagi pekerjaan ini.');
        }

        return DB::transaction(function () use ($job, $operator, $actor): PrintJob {
            $locked = PrintJob::query()->whereKey($job->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('orderItem', 'operators');

            $this->assertAssignable($locked, $operator);

            $before = $this->jobSnapshot($locked);

            $active = $locked->operators()->whereNull('ended_at')->lockForUpdate()->first();
            if ($active !== null) {
                throw ValidationException::withMessages([
                    'operator' => 'Pekerjaan sudah dipegang operator lain. Lakukan handover terlebih dahulu.',
                ]);
            }

            $locked->update([
                'assigned_to' => $operator->id,
                'assigned_by' => $actor->id,
            ]);

            PrintJobOperator::create([
                'print_job_id' => $locked->id,
                'operator_id' => $operator->id,
                'started_at' => now(),
                'ended_at' => null,
                'reason' => null,
            ]);

            $this->audit->record(
                action: 'production.assigned',
                actor: $actor,
                modelType: PrintJob::class,
                modelId: $locked->id,
                before: $before,
                after: $this->jobSnapshot($locked->fresh()),
            );

            return $locked->fresh(['assignee', 'assigner', 'operators.operator', 'orderItem.order.customer']);
        });
    }

    /**
     * Ambil pekerjaan sendiri (self-claim) oleh operator berwenang (BR-09).
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function claim(PrintJob $job, User $actor): PrintJob
    {
        if (! $actor->can('claim', $job)) {
            throw new AuthorizationException('Anda tidak berwenang mengambil pekerjaan ini.');
        }

        return DB::transaction(function () use ($job, $actor): PrintJob {
            $locked = PrintJob::query()->whereKey($job->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('orderItem');

            $before = $this->jobSnapshot($locked);

            $active = PrintJobOperator::query()
                ->where('print_job_id', $locked->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($active !== null) {
                throw ValidationException::withMessages([
                    'operator' => 'Pekerjaan sudah dipegang operator lain.',
                ]);
            }

            $locked->update([
                'assigned_to' => $actor->id,
                'assigned_by' => $actor->id,
            ]);

            PrintJobOperator::create([
                'print_job_id' => $locked->id,
                'operator_id' => $actor->id,
                'started_at' => now(),
                'ended_at' => null,
                'reason' => null,
            ]);

            $this->audit->record(
                action: 'production.claimed',
                actor: $actor,
                modelType: PrintJob::class,
                modelId: $locked->id,
                before: $before,
                after: $this->jobSnapshot($locked->fresh()),
            );

            return $locked->fresh(['assignee', 'assigner', 'operators.operator', 'orderItem.order.customer']);
        });
    }

    /**
     * Catat progres/hasil cetak (qty rencana vs aktual, mulai/selesai) (BR-17).
     * Setelah qty aktual dicatat, item dapat dipindah ke printed (AC-04-7).
     *
     * @param  array{qty_planned?: string|int|float|null, qty_actual?: string|int|float|null, started_at?: mixed, finished_at?: mixed, mark_printed?: bool}  $data
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function recordProgress(PrintJob $job, array $data, User $actor): PrintJob
    {
        $job->loadMissing('operators', 'orderItem');

        if (! $actor->can('recordProgress', $job)) {
            throw new AuthorizationException('Anda tidak berwenang mencatat progres pekerjaan ini.');
        }

        return DB::transaction(function () use ($job, $data, $actor): PrintJob {
            $locked = PrintJob::query()->whereKey($job->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('orderItem');

            $beforeJob = $this->jobSnapshot($locked);
            $beforeItem = $locked->orderItem?->status?->value;

            $updates = [];

            if (array_key_exists('qty_planned', $data) && $data['qty_planned'] !== null && $data['qty_planned'] !== '') {
                $updates['qty_planned'] = $this->amount($data['qty_planned']);
            }

            $hasActual = array_key_exists('qty_actual', $data) && $data['qty_actual'] !== null && $data['qty_actual'] !== '';
            if ($hasActual) {
                $updates['qty_actual'] = $this->amount($data['qty_actual']);
            }

            if (! empty($data['started_at'])) {
                $updates['started_at'] = Carbon::parse($data['started_at']);
            }

            if (! empty($data['finished_at'])) {
                $updates['finished_at'] = Carbon::parse($data['finished_at']);
            }

            $markPrinted = (bool) ($data['mark_printed'] ?? false);

            if (($markPrinted || ! empty($updates['finished_at'])) && ! $hasActual && $locked->qty_actual === null && ! isset($updates['qty_actual'])) {
                throw ValidationException::withMessages([
                    'qty_actual' => 'Qty aktual wajib dicatat sebelum pekerjaan dinyatakan selesai/printed.',
                ]);
            }

            if ($markPrinted || $hasActual || ! empty($updates['started_at']) || ! empty($updates['finished_at'])) {
                $updates['status'] = PrintJobStatus::InProgress;
            }

            if ($markPrinted || ! empty($updates['finished_at'])) {
                $updates['status'] = PrintJobStatus::Completed;
                $updates['finished_at'] = $updates['finished_at'] ?? now();
            }

            if ($updates !== []) {
                $locked->update($updates);
            }

            $locked->refresh()->load('orderItem');

            if (($locked->status === PrintJobStatus::InProgress || $locked->started_at !== null)
                && $locked->orderItem->status === ItemStatus::QueuedProduction) {
                $this->orders->changeItemStatus($locked->orderItem, ItemStatus::Printing, $actor, 'Pekerjaan cetak dimulai/progres dicatat.');
            }

            if ($locked->status === PrintJobStatus::Completed && $locked->qty_actual !== null) {
                // AC-04-7: setelah qty aktual dicatat, item dapat dipindah ke printed.
                if (in_array($locked->orderItem->status, [ItemStatus::QueuedProduction, ItemStatus::Printing], true)) {
                    $this->orders->changeItemStatus($locked->orderItem, ItemStatus::Printed, $actor, 'Pekerjaan cetak selesai, qty aktual tercatat.');
                }
            }

            $this->audit->record(
                action: 'production.progress_recorded',
                actor: $actor,
                modelType: PrintJob::class,
                modelId: $locked->id,
                before: array_merge($beforeJob, ['item_status' => $beforeItem]),
                after: array_merge($this->jobSnapshot($locked->fresh()), ['item_status' => $locked->fresh()->orderItem?->status?->value]),
            );

            return $locked->fresh(['assignee', 'assigner', 'operators.operator', 'orderItem.order.customer', 'reprintRequests.requester', 'reprintRequests.approver']);
        });
    }

    /**
     * Handover/pergantian operator atau shift (BR-10).
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handover(PrintJob $job, User $newOperator, string $reason, User $actor): PrintJob
    {
        $job->loadMissing('operators');

        if (! $actor->can('handover', $job)) {
            throw new AuthorizationException('Anda tidak berwenang melakukan handover pekerjaan ini.');
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan handover minimal 5 karakter.',
            ]);
        }

        return DB::transaction(function () use ($job, $newOperator, $reason, $actor): PrintJob {
            $locked = PrintJob::query()->whereKey($job->id)->lockForUpdate()->firstOrFail();
            $locked->loadMissing('orderItem');

            $this->assertAssignable($locked, $newOperator);

            $active = PrintJobOperator::query()
                ->where('print_job_id', $locked->id)
                ->whereNull('ended_at')
                ->lockForUpdate()
                ->first();

            if ($active === null) {
                throw ValidationException::withMessages([
                    'operator' => 'Tidak ada operator aktif untuk di-handover.',
                ]);
            }

            $before = $this->jobSnapshot($locked);

            $active->update([
                'ended_at' => now(),
                'reason' => $reason,
            ]);

            PrintJobOperator::create([
                'print_job_id' => $locked->id,
                'operator_id' => $newOperator->id,
                'started_at' => now(),
                'ended_at' => null,
                'reason' => null,
            ]);

            $locked->update([
                'assigned_to' => $newOperator->id,
                'assigned_by' => $actor->id,
            ]);

            $this->audit->record(
                action: 'production.handover',
                actor: $actor,
                modelType: PrintJob::class,
                modelId: $locked->id,
                before: array_merge($before, ['active_operator_id' => $active->operator_id]),
                after: array_merge($this->jobSnapshot($locked->fresh()), [
                    'active_operator_id' => $newOperator->id,
                    'handover_reason' => $reason,
                ]),
            );

            return $locked->fresh(['assignee', 'assigner', 'operators.operator', 'orderItem.order.customer']);
        });
    }

    /**
     * Ajukan cetak ulang (BR-11). Tidak mengubah qty aktual.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function requestReprint(PrintJob $job, string|int|float $qty, string $reason, User $actor): ReprintRequest
    {
        $job->loadMissing('orderItem');

        if (! $actor->can('requestReprint', $job)) {
            throw new AuthorizationException('Anda tidak berwenang mengajukan cetak ulang.');
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan cetak ulang minimal 5 karakter.',
            ]);
        }

        $qtyAmount = $this->amount($qty);
        if (bccomp($qtyAmount, '0', 2) <= 0) {
            throw ValidationException::withMessages([
                'qty' => 'Jumlah cetak ulang harus lebih dari 0.',
            ]);
        }

        return DB::transaction(function () use ($job, $qtyAmount, $reason, $actor): ReprintRequest {
            $locked = PrintJob::query()->whereKey($job->id)->lockForUpdate()->firstOrFail();

            $request = ReprintRequest::create([
                'print_job_id' => $locked->id,
                'qty' => $qtyAmount,
                'reason' => $reason,
                'requested_by' => $actor->id,
                'status' => ReprintStatus::Pending,
                'actual_qty' => null,
            ]);

            $this->audit->record(
                action: 'production.reprint_requested',
                actor: $actor,
                modelType: ReprintRequest::class,
                modelId: $request->id,
                after: [
                    'print_job_id' => $locked->id,
                    'qty' => $qtyAmount,
                    'status' => ReprintStatus::Pending->value,
                ],
            );

            return $request->load(['requester', 'approver', 'printJob.orderItem.order.customer']);
        });
    }

    /**
     * Setujui cetak ulang (BR-11). Hanya approval yang boleh mengubah qty aktual.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function approveReprint(ReprintRequest $reprint, User $actor, ?string $actualQty = null): ReprintRequest
    {
        if (! $actor->can('approveReprint', $reprint)) {
            throw new AuthorizationException('Anda tidak berwenang menyetujui cetak ulang.');
        }

        return DB::transaction(function () use ($reprint, $actor, $actualQty): ReprintRequest {
            $locked = ReprintRequest::query()->whereKey($reprint->id)->lockForUpdate()->firstOrFail();
            $job = PrintJob::query()->whereKey($locked->print_job_id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ReprintStatus::Pending) {
                throw ValidationException::withMessages([
                    'reprint' => 'Pengajuan cetak ulang ini sudah diproses.',
                ]);
            }

            $before = [
                'status' => $locked->status->value,
                'job_qty_actual' => $job->qty_actual !== null ? (string) $job->qty_actual : null,
            ];

            $updates = [
                'status' => ReprintStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ];

            if ($actualQty !== null && $actualQty !== '') {
                $approvedActual = $this->amount($actualQty);
                $updates['actual_qty'] = $approvedActual;
                $job->update([
                    'qty_actual' => bcadd($this->amount($job->qty_actual ?? 0), $approvedActual, 2),
                ]);
            }

            $locked->update($updates);

            $this->audit->record(
                action: 'production.reprint_approved',
                actor: $actor,
                modelType: ReprintRequest::class,
                modelId: $locked->id,
                before: $before,
                after: [
                    'status' => ReprintStatus::Approved->value,
                    'actual_qty' => $locked->fresh()->actual_qty !== null ? (string) $locked->fresh()->actual_qty : null,
                    'job_qty_actual' => $job->fresh()->qty_actual !== null ? (string) $job->fresh()->qty_actual : null,
                ],
            );

            return $locked->fresh(['requester', 'approver', 'printJob.orderItem.order.customer']);
        });
    }

    /**
     * Tolak cetak ulang (BR-11).
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function rejectReprint(ReprintRequest $reprint, User $actor): ReprintRequest
    {
        if (! $actor->can('approveReprint', $reprint)) {
            throw new AuthorizationException('Anda tidak berwenang menolak cetak ulang.');
        }

        return DB::transaction(function () use ($reprint, $actor): ReprintRequest {
            $locked = ReprintRequest::query()->whereKey($reprint->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ReprintStatus::Pending) {
                throw ValidationException::withMessages([
                    'reprint' => 'Pengajuan cetak ulang ini sudah diproses.',
                ]);
            }

            $locked->update([
                'status' => ReprintStatus::Rejected,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->audit->record(
                action: 'production.reprint_rejected',
                actor: $actor,
                modelType: ReprintRequest::class,
                modelId: $locked->id,
                before: ['status' => ReprintStatus::Pending->value],
                after: ['status' => ReprintStatus::Rejected->value],
            );

            return $locked->fresh(['requester', 'approver', 'printJob.orderItem.order.customer']);
        });
    }

    /**
     * @throws ValidationException
     */
    private function assertAssignable(PrintJob $job, User $operator): void
    {
        $item = $job->orderItem;
        if ($item === null || ! in_array($item->status, [ItemStatus::QueuedProduction, ItemStatus::Printing], true)) {
            throw ValidationException::withMessages([
                'job' => 'Pekerjaan tidak dalam status yang dapat ditugaskan.',
            ]);
        }

        if (! $operator->can('production.progress')) {
            throw ValidationException::withMessages([
                'operator' => 'Pengguna yang dipilih bukan operator produksi yang berwenang.',
            ]);
        }
    }

    private function priorityRank(?Priority $priority): int
    {
        return match ($priority) {
            Priority::Urgent => 0,
            Priority::H1 => 1,
            Priority::Normal => 2,
            default => 3,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function jobSnapshot(PrintJob $job): array
    {
        return [
            'id' => $job->id,
            'order_item_id' => $job->order_item_id,
            'queue_class' => $job->queue_class?->value,
            'status' => $job->status?->value,
            'assigned_to' => $job->assigned_to,
            'assigned_by' => $job->assigned_by,
            'qty_planned' => $job->qty_planned !== null ? (string) $job->qty_planned : null,
            'qty_actual' => $job->qty_actual !== null ? (string) $job->qty_actual : null,
        ];
    }

    private function amount(string|int|float $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
