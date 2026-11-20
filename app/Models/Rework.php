<?php

namespace App\Models;

use App\Enums\RedoStage;
use App\Enums\ReworkStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pekerjaan ulang setelah QC gagal (MOD-05).
 */
#[Fillable([
    'order_item_id',
    'qc_record_id',
    'finishing_job_id',
    'reason',
    'redo_stage',
    'status',
    'done_by',
    'done_at',
])]
class Rework extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'redo_stage' => RedoStage::class,
            'status' => ReworkStatus::class,
            'done_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * @return BelongsTo<QcRecord|null, $this>
     */
    public function qcRecord(): BelongsTo
    {
        return $this->belongsTo(QcRecord::class);
    }

    /**
     * @return BelongsTo<FinishingJob|null, $this>
     */
    public function finishingJob(): BelongsTo
    {
        return $this->belongsTo(FinishingJob::class);
    }

    /**
     * @return BelongsTo<User|null, $this>
     */
    public function doer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }

    /**
     * @return BelongsTo<User|null, $this>
     */
    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }
}
