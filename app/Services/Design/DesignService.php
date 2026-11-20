<?php

namespace App\Services\Design;

use App\Enums\ChangeRequestStatus;
use App\Enums\DesignFileKind;
use App\Enums\DesignJobStatus;
use App\Enums\ItemStatus;
use App\Enums\UserRole;
use App\Models\ChangeRequest;
use App\Models\DesignApproval;
use App\Models\DesignFile;
use App\Models\DesignJob;
use App\Models\DesignRevision;
use App\Models\ItemStatusHistory;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Order\OrderService;
use App\Services\Production\ProductionService;
use App\Services\System\AuditLogService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Business logic desain (MOD-03, PH-03) — domain saja, tanpa HTTP/UI.
 *
 * AC-03-2: self-claim tugas desain dari antrean.
 * AC-03-7: serah terima item ACC ke produksi oleh Admin.
 * AC-03-8: riwayat versi, revisi, dan ACC beserta pelakunya.
 */
class DesignService
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly AuditLogService $audit,
        private readonly ProductionService $production,
    ) {}

    /**
     * Ambil tugas desain dari antrean (self-claim).
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function claim(OrderItem $item, User $actor): DesignJob
    {
        if (! $actor->can('designs.claim')) {
            throw new AuthorizationException('Anda tidak berwenang mengambil tugas desain.');
        }

        return DB::transaction(function () use ($item, $actor): DesignJob {
            $locked = OrderItem::query()
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== ItemStatus::QueuedDesign) {
                throw ValidationException::withMessages([
                    'item' => 'Item tidak dalam antrean desain.',
                ]);
            }

            $job = DesignJob::create([
                'order_item_id' => $locked->id,
                'designer_id' => $actor->id,
                'assigned_by' => $actor->id,
                'assigned_at' => now(),
                'status' => DesignJobStatus::Assigned,
                'revision_cycle_used' => 0,
            ]);

            $this->orders->changeItemStatus(
                $locked,
                ItemStatus::Designing,
                $actor,
                'Desain diambil oleh '.$actor->name,
            );

            $this->audit->record(
                action: 'design.claimed',
                actor: $actor,
                modelType: DesignJob::class,
                modelId: $job->id,
                before: ['status' => ItemStatus::QueuedDesign->value],
                after: [
                    'status' => ItemStatus::Designing->value,
                    'design_job_id' => $job->id,
                    'designer_id' => $actor->id,
                ],
            );

            return $job;
        });
    }

    /**
     * Unggah versi baru file desain (versioned, tidak menimpa lama).
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function upload(DesignJob $job, UploadedFile $file, User $actor): DesignFile
    {
        $job->loadMissing('orderItem');

        $isOwner = $job->designer_id === $actor->id;
        $isAdminLevel = $actor->can('designs.upload') && ! $actor->hasRole(UserRole::Designer);

        if (! $isOwner && ! $isAdminLevel) {
            throw new AuthorizationException('Anda tidak berwenang mengunggah file desain ini.');
        }

        $item = $job->orderItem;

        if ($item === null || ! in_array($item->status, [ItemStatus::Designing, ItemStatus::Revision], true)) {
            throw ValidationException::withMessages([
                'item' => 'File desain hanya dapat diunggah saat item sedang didesain atau dalam revisi.',
            ]);
        }

        return DB::transaction(function () use ($job, $file, $actor): DesignFile {
            $lockedJob = DesignJob::query()
                ->whereKey($job->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedJob->loadMissing('orderItem');

            if (! in_array($lockedJob->orderItem?->status, [ItemStatus::Designing, ItemStatus::Revision], true)) {
                throw ValidationException::withMessages([
                    'item' => 'File desain hanya dapat diunggah saat item sedang didesain atau dalam revisi.',
                ]);
            }

            $previousVersion = (int) (DesignFile::query()
                ->where('design_job_id', $lockedJob->id)
                ->max('version') ?? 0);

            $version = $previousVersion + 1;

            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');

            $storedPath = $file->storeAs(
                'design/'.$lockedJob->id,
                'v'.$version.'.'.$extension,
                'local',
            );

            if ($storedPath === false || $storedPath === null) {
                throw ValidationException::withMessages([
                    'file' => 'Gagal menyimpan file desain.',
                ]);
            }

            $designFile = DesignFile::create([
                'design_job_id' => $lockedJob->id,
                'version' => $version,
                'kind' => DesignFileKind::Draft,
                'path' => $storedPath,
                'uploaded_by' => $actor->id,
            ]);

            $this->audit->record(
                action: 'design.file.uploaded',
                actor: $actor,
                modelType: DesignFile::class,
                modelId: $designFile->id,
                before: ['version' => $previousVersion > 0 ? $previousVersion : null],
                after: [
                    'version' => $version,
                    'path' => $storedPath,
                    'kind' => DesignFileKind::Draft->value,
                    'design_job_id' => $lockedJob->id,
                ],
            );

            return $designFile;
        });
    }

    /**
     * Ajukan satu siklus revisi (BR-04, BR-17). Siklus ke-4 ditolak dan
     * menawarkan Change Request.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function requestRevision(DesignJob $job, string $note, User $actor): DesignRevision
    {
        if (! $actor->can('designs.revise')) {
            throw new AuthorizationException('Anda tidak berwenang meminta revisi desain.');
        }

        $note = trim($note);
        if (mb_strlen($note) < 5) {
            throw ValidationException::withMessages([
                'note' => 'Catatan revisi minimal 5 karakter.',
                'request_note' => 'Catatan revisi minimal 5 karakter.',
            ]);
        }

        $job->loadMissing('orderItem');
        $item = $job->orderItem;

        if ($item === null || ! in_array($item->status, [ItemStatus::Designing, ItemStatus::Revision], true)) {
            throw ValidationException::withMessages([
                'item' => 'Revisi hanya dapat diminta saat item sedang didesain atau dalam revisi.',
            ]);
        }

        $nextCycle = (int) $job->revision_cycle_used + 1;

        $hasApprovedCr = false;
        if ($nextCycle > 3) {
            $hasApprovedCr = ChangeRequest::query()
                ->where('order_item_id', $item->id)
                ->where('status', ChangeRequestStatus::Approved)
                ->whereNotNull('applied_at')
                ->exists();
        }

        if ($nextCycle > 3 && ! $hasApprovedCr) {
            throw ValidationException::withMessages([
                'revision' => 'Siklus revisi gratis (3) habis. Ajukan Change Request.',
                'change_request_offered' => true,
            ]);
        }

        return DB::transaction(function () use ($job, $item, $note, $actor, $nextCycle): DesignRevision {
            $lockedJob = DesignJob::query()
                ->whereKey($job->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedJob->loadMissing('orderItem');
            $lockedItem = $lockedJob->orderItem;

            if ($lockedItem === null || ! in_array($lockedItem->status, [ItemStatus::Designing, ItemStatus::Revision], true)) {
                throw ValidationException::withMessages([
                    'item' => 'Revisi hanya dapat diminta saat item sedang didesain atau dalam revisi.',
                ]);
            }

            $beforeCycle = (int) $lockedJob->revision_cycle_used;

            $revision = DesignRevision::create([
                'design_job_id' => $lockedJob->id,
                'cycle_no' => $nextCycle,
                'request_note' => $note,
                'requested_by' => $actor->id,
                'requested_at' => now(),
            ]);

            $lockedJob->update(['revision_cycle_used' => $nextCycle]);

            $this->orders->changeItemStatus(
                $lockedItem,
                ItemStatus::Revision,
                $actor,
                "Revisi siklus ke-{$nextCycle}",
            );

            $this->audit->record(
                action: 'design.revision.requested',
                actor: $actor,
                modelType: DesignRevision::class,
                modelId: $revision->id,
                before: [
                    'revision_cycle_used' => $beforeCycle,
                    'item_status' => $item->status->value,
                ],
                after: [
                    'revision_cycle_used' => $nextCycle,
                    'item_status' => ItemStatus::Revision->value,
                    'design_revision_id' => $revision->id,
                    'cycle_no' => $nextCycle,
                ],
            );

            return $revision;
        });
    }

    /**
     * Ajukan desain untuk ACC (AC-03-5, BR-17).
     * Item: Designing/Revision -> AwaitingAcc.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function submitForApproval(DesignJob $job, User $actor): DesignJob
    {
        $job->loadMissing('orderItem');
        $item = $job->orderItem;

        if (! ($job->designer_id === $actor->id || $actor->can('designs.approve'))) {
            throw new AuthorizationException('Anda tidak berwenang mengajukan ACC desain ini.');
        }

        if ($item === null || ! in_array($item->status, [ItemStatus::Designing, ItemStatus::Revision], true)) {
            throw ValidationException::withMessages([
                'item' => 'Desain hanya dapat diajukan ACC saat item sedang didesain atau dalam revisi.',
            ]);
        }

        return DB::transaction(function () use ($job, $actor): DesignJob {
            $lockedJob = DesignJob::query()
                ->whereKey($job->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedJob->loadMissing('orderItem');
            $lockedItem = $lockedJob->orderItem;

            if ($lockedItem === null || ! in_array($lockedItem->status, [ItemStatus::Designing, ItemStatus::Revision], true)) {
                throw ValidationException::withMessages([
                    'item' => 'Desain hanya dapat diajukan ACC saat item sedang didesain atau dalam revisi.',
                ]);
            }

            $beforeStatus = $lockedItem->status;

            $this->orders->changeItemStatus(
                $lockedItem,
                ItemStatus::AwaitingAcc,
                $actor,
                'Desain diajukan untuk ACC',
            );

            $lockedJob->update(['status' => DesignJobStatus::Submitted]);

            $this->audit->record(
                action: 'design.submitted',
                actor: $actor,
                modelType: DesignJob::class,
                modelId: $lockedJob->id,
                before: ['item_status' => $beforeStatus->value],
                after: [
                    'item_status' => ItemStatus::AwaitingAcc->value,
                    'job_status' => DesignJobStatus::Submitted->value,
                    'design_job_id' => $lockedJob->id,
                ],
            );

            return $lockedJob->fresh();
        });
    }

    /**
     * ACC desain: kunci versi final (AC-03-5, BR-05, BR-17).
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function approve(
        DesignJob $job,
        User $actor,
        string $method = 'internal',
        ?string $note = null,
        ?int $designFileId = null,
    ): DesignApproval {
        if (! $actor->can('designs.approve')) {
            throw new AuthorizationException('Anda tidak berwenang memberikan ACC desain.');
        }

        $job->loadMissing('orderItem');
        $item = $job->orderItem;

        if ($item === null || $item->status !== ItemStatus::AwaitingAcc) {
            throw ValidationException::withMessages([
                'item' => 'ACC hanya dapat diberikan saat item menunggu ACC.',
            ]);
        }

        return DB::transaction(function () use ($job, $actor, $method, $note, $designFileId): DesignApproval {
            $lockedJob = DesignJob::query()
                ->whereKey($job->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedJob->loadMissing('orderItem');
            $lockedItem = $lockedJob->orderItem;

            if ($lockedItem === null || $lockedItem->status !== ItemStatus::AwaitingAcc) {
                throw ValidationException::withMessages([
                    'item' => 'ACC hanya dapat diberikan saat item menunggu ACC.',
                ]);
            }

            if ($designFileId !== null) {
                $file = DesignFile::query()
                    ->where('design_job_id', $lockedJob->id)
                    ->whereKey($designFileId)
                    ->first();
            } else {
                $file = DesignFile::query()
                    ->where('design_job_id', $lockedJob->id)
                    ->orderByDesc('version')
                    ->orderByDesc('id')
                    ->first();
            }

            if ($file === null) {
                throw ValidationException::withMessages([
                    'design_file_id' => 'File desain yang akan di-ACC tidak ditemukan.',
                ]);
            }

            if ($file->kind === DesignFileKind::Final) {
                throw ValidationException::withMessages([
                    'design_file_id' => 'File ini sudah menjadi final; ACC ulang wajib memilih versi baru.',
                ]);
            }

            $approvalCount = DesignApproval::query()
                ->where('design_job_id', $lockedJob->id)
                ->count();

            $appliedChangeRequestCount = ChangeRequest::query()
                ->where('order_item_id', $lockedItem->id)
                ->where('status', ChangeRequestStatus::Approved)
                ->whereNotNull('applied_at')
                ->count();

            // ACC ulang hanya sah bila didahului CR yang disetujui dan diterapkan (BR-05).
            // Satu CR dipakai untuk satu ACC ulang: file final lama diturunkan menjadi
            // draft agar versi baru bisa dikunci, tanpa menghapus riwayat approval.
            if ($approvalCount > 0 && $appliedChangeRequestCount >= $approvalCount) {
                DesignFile::query()
                    ->where('design_job_id', $lockedJob->id)
                    ->where('kind', DesignFileKind::Final)
                    ->where('id', '!=', $file->id)
                    ->update(['kind' => DesignFileKind::Draft]);
            }

            $existingFinal = DesignFile::query()
                ->where('design_job_id', $lockedJob->id)
                ->where('kind', DesignFileKind::Final)
                ->where('id', '!=', $file->id)
                ->exists();

            if ($existingFinal) {
                throw ValidationException::withMessages([
                    'design_file_id' => 'Satu pekerjaan hanya boleh memiliki satu file final.',
                ]);
            }

            $beforeKind = $file->kind;

            $file->update(['kind' => DesignFileKind::Final]);

            $approval = DesignApproval::create([
                'design_job_id' => $lockedJob->id,
                'design_file_id' => $file->id,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'method' => $method ?: 'internal',
                'note' => $note !== null && trim($note) !== '' ? trim($note) : null,
            ]);

            $this->orders->changeItemStatus(
                $lockedItem,
                ItemStatus::Acc,
                $actor,
                'Desain di-ACC',
            );

            $lockedJob->update(['status' => DesignJobStatus::Approved]);

            $this->audit->record(
                action: 'design.approved',
                actor: $actor,
                modelType: DesignApproval::class,
                modelId: $approval->id,
                before: [
                    'item_status' => ItemStatus::AwaitingAcc->value,
                    'file_kind' => $beforeKind?->value,
                    'design_file_id' => $file->id,
                ],
                after: [
                    'item_status' => ItemStatus::Acc->value,
                    'file_kind' => DesignFileKind::Final->value,
                    'design_file_id' => $file->id,
                    'design_approval_id' => $approval->id,
                    'method' => $method ?: 'internal',
                ],
            );

            return $approval;
        });
    }

    /**
     * Serahkan item yang sudah ACC ke antrean produksi (AC-03-7, BR-17).
     * Sesuai AC, hanya Admin yang berwenang melakukan serah terima ini.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function handoffToProduction(OrderItem $item, User $actor): OrderItem
    {
        if (! $actor->hasRole(UserRole::Admin)) {
            throw new AuthorizationException('Hanya Admin yang dapat menyerahkan item ke produksi.');
        }

        return DB::transaction(function () use ($item, $actor): OrderItem {
            $locked = OrderItem::query()
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== ItemStatus::Acc) {
                throw ValidationException::withMessages([
                    'item' => 'Item harus berstatus ACC sebelum diserahkan ke produksi.',
                ]);
            }

            $from = $locked->status;

            // OrderService::assertItemMutable() menolak mutasi dari ACC (BR-05)
            // dan secara eksplisit melarang bypass ditambahkan di sana.
            // AC-03-7 adalah transisi lanjut yang sah, jadi ditulis di sini
            // dengan riwayat status yang sama seperti changeItemStatus (integritas #7).
            $locked->update(['status' => ItemStatus::QueuedProduction]);

            // Intake produksi (MOD-04, BR-06/BR-07): buat print job + queue_class.
            $this->production->createForItem($locked->fresh());

            ItemStatusHistory::create([
                'order_item_id' => $locked->id,
                'from_status' => $from?->value,
                'to_status' => ItemStatus::QueuedProduction->value,
                'changed_by' => $actor->id,
                'note' => 'Desain diserahkan ke produksi',
            ]);

            $this->audit->record(
                action: 'design.handed_off_to_production',
                actor: $actor,
                modelType: OrderItem::class,
                modelId: $locked->id,
                before: ['item_status' => $from?->value],
                after: ['item_status' => ItemStatus::QueuedProduction->value],
            );

            return $locked->fresh();
        });
    }

    /**
     * Ajukan Change Request pada item ACC (AC-03-6, BR-05, BR-17).
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function requestChangeRequest(
        OrderItem $item,
        User $actor,
        string $reason,
        int|float|string $costImpact,
        mixed $deadlineImpact,
        ?int $designFileId = null,
    ): ChangeRequest {
        if (! $actor->can('designs.change_request')) {
            throw new AuthorizationException('Anda tidak berwenang mengajukan Change Request.');
        }

        $reason = trim($reason);
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'reason' => 'Alasan Change Request minimal 5 karakter.',
            ]);
        }

        $item->loadMissing('order');
        if ($item->status !== ItemStatus::Acc) {
            throw ValidationException::withMessages([
                'item' => 'Change Request hanya dapat diajukan untuk item yang sudah ACC.',
            ]);
        }

        $job = DesignJob::query()->where('order_item_id', $item->id)->latest('id')->first();

        $file = null;
        if ($designFileId !== null) {
            if ($job === null) {
                throw ValidationException::withMessages([
                    'design_file_id' => 'Pekerjaan desain tidak ditemukan.',
                ]);
            }
            $file = DesignFile::query()
                ->where('design_job_id', $job->id)
                ->whereKey($designFileId)
                ->first();
            if ($file === null) {
                throw ValidationException::withMessages([
                    'design_file_id' => 'File desain tidak ditemukan pada pekerjaan ini.',
                ]);
            }
        }

        return DB::transaction(function () use ($item, $actor, $reason, $costImpact, $deadlineImpact, $file): ChangeRequest {
            $cr = ChangeRequest::create([
                'order_item_id' => $item->id,
                'design_file_id' => $file?->id,
                'reason' => $reason,
                'cost_impact' => $this->amount($costImpact),
                'deadline_impact' => $deadlineImpact,
                'status' => ChangeRequestStatus::Pending,
            ]);

            $this->audit->record(
                action: 'design.change_request.raised',
                actor: $actor,
                modelType: ChangeRequest::class,
                modelId: $cr->id,
                before: ['item_status' => ItemStatus::Acc->value],
                after: [
                    'status' => ChangeRequestStatus::Pending->value,
                    'cost_impact' => (string) $cr->cost_impact,
                    'deadline_impact' => $cr->deadline_impact?->toDateTimeString(),
                    'design_file_id' => $cr->design_file_id,
                ],
                reason: $reason,
            );

            return $cr;
        });
    }

    /**
     * Setujui Change Request → buka kembali item (reopen) (AC-03-6, BR-05, BR-17).
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function approveChangeRequest(ChangeRequest $cr, User $actor): ChangeRequest
    {
        if (! $actor->can('designs.approve')) {
            throw new AuthorizationException('Anda tidak berwenang menyetujui Change Request.');
        }

        $cr->loadMissing('orderItem');

        if ($cr->status !== ChangeRequestStatus::Pending) {
            throw ValidationException::withMessages([
                'change_request' => 'Hanya Change Request berstatus menunggu yang dapat disetujui.',
            ]);
        }

        return DB::transaction(function () use ($cr, $actor): ChangeRequest {
            $locked = ChangeRequest::query()
                ->whereKey($cr->id)
                ->lockForUpdate()
                ->firstOrFail();
            $locked->loadMissing('orderItem');

            if ($locked->status !== ChangeRequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'change_request' => 'Hanya Change Request berstatus menunggu yang dapat disetujui.',
                ]);
            }

            $locked->update([
                'status' => ChangeRequestStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->applyChangeRequest($locked, $actor);

            $this->audit->record(
                action: 'design.change_request.approved',
                actor: $actor,
                modelType: ChangeRequest::class,
                modelId: $locked->id,
                before: ['status' => ChangeRequestStatus::Pending->value],
                after: ['status' => ChangeRequestStatus::Approved->value],
            );

            return $locked->fresh();
        });
    }

    /**
     * Terapkan Change Request yang sudah disetujui: buka kembali item ACC.
     *
     * @throws ValidationException
     */
    public function applyChangeRequest(ChangeRequest $cr, User $actor): ChangeRequest
    {
        $cr->loadMissing('orderItem');

        if ($cr->status !== ChangeRequestStatus::Approved) {
            throw ValidationException::withMessages([
                'change_request' => 'Change Request belum disetujui.',
            ]);
        }

        if ($cr->approved_by === null) {
            throw ValidationException::withMessages([
                'change_request' => 'Change Request belum memiliki penyetuju.',
            ]);
        }

        if ($cr->applied_at !== null) {
            return $cr;
        }

        $item = $cr->orderItem;
        if ($item === null || $item->status !== ItemStatus::Acc) {
            throw ValidationException::withMessages([
                'item' => 'Item tidak dalam status ACC.',
            ]);
        }

        $this->orders->reopenItemForApprovedChangeRequest($item, $cr, $actor);

        return $cr->fresh();
    }

    /**
     * Tolak Change Request — status item tidak berubah.
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function rejectChangeRequest(ChangeRequest $cr, User $actor): ChangeRequest
    {
        if (! $actor->can('designs.approve')) {
            throw new AuthorizationException('Anda tidak berwenang menolak Change Request.');
        }

        $cr->loadMissing('orderItem');

        if ($cr->status !== ChangeRequestStatus::Pending) {
            throw ValidationException::withMessages([
                'change_request' => 'Hanya Change Request berstatus menunggu yang dapat ditolak.',
            ]);
        }

        return DB::transaction(function () use ($cr, $actor): ChangeRequest {
            $locked = ChangeRequest::query()
                ->whereKey($cr->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== ChangeRequestStatus::Pending) {
                throw ValidationException::withMessages([
                    'change_request' => 'Hanya Change Request berstatus menunggu yang dapat ditolak.',
                ]);
            }

            $locked->update([
                'status' => ChangeRequestStatus::Rejected,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ]);

            $this->audit->record(
                action: 'design.change_request.rejected',
                actor: $actor,
                modelType: ChangeRequest::class,
                modelId: $locked->id,
                before: ['status' => ChangeRequestStatus::Pending->value],
                after: ['status' => ChangeRequestStatus::Rejected->value],
            );

            return $locked->fresh();
        });
    }

    /**
     * Gabungkan versi file, revisi, dan ACC menjadi satu urutan riwayat (AC-03-8).
     * Setiap entri membawa pelakunya agar dapat ditelusuri (BR-17).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function history(DesignJob $job): Collection
    {
        $job->loadMissing([
            'files.uploader',
            'revisions.requester',
            'approvals.approver',
            'approvals.designFile',
        ]);

        $events = collect();

        foreach ($job->files as $file) {
            $events->push([
                'type' => 'file',
                'label' => 'Versi v'.$file->version,
                'detail' => $file->kind?->label(),
                'actor' => $file->uploader?->name ?? '-',
                'happened_at' => $file->created_at,
                'sort' => 0,
                'sequence' => (int) $file->version,
            ]);
        }

        foreach ($job->revisions as $revision) {
            $events->push([
                'type' => 'revision',
                'label' => 'Revisi siklus ke-'.$revision->cycle_no,
                'detail' => $revision->request_note,
                'actor' => $revision->requester?->name ?? '-',
                'happened_at' => $revision->requested_at,
                'sort' => 1,
                'sequence' => (int) $revision->cycle_no,
            ]);
        }

        foreach ($job->approvals as $approval) {
            $version = $approval->designFile?->version;

            $events->push([
                'type' => 'approval',
                'label' => $version !== null ? 'ACC v'.$version : 'ACC',
                'detail' => $approval->method,
                'actor' => $approval->approver?->name ?? '-',
                'happened_at' => $approval->approved_at,
                'sort' => 2,
                'sequence' => (int) $approval->id,
            ]);
        }

        return $events
            ->sort(function (array $a, array $b): int {
                return [
                    $a['happened_at']?->getTimestamp() ?? PHP_INT_MAX,
                    $a['sort'],
                    $a['sequence'],
                ] <=> [
                    $b['happened_at']?->getTimestamp() ?? PHP_INT_MAX,
                    $b['sort'],
                    $b['sequence'],
                ];
            })
            ->values();
    }

    private function amount(int|float|string|null $value): string
    {
        if ($value === null) {
            return '0.00';
        }

        if (is_float($value)) {
            $value = number_format($value, 2, '.', '');
        }

        return bcadd((string) $value, '0', 2);
    }
}
