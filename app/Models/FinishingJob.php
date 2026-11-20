<?php

namespace App\Models;

use App\Enums\FinishingJobStatus;
use App\Enums\FinishingType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Pekerjaan finishing per item (MOD-05).
 */
#[Fillable([
    'order_item_id',
    'type',
    'status',
    'assigned_to',
    'assigned_by',
    'progress',
    'progress_note',
    'started_at',
    'finished_at',
])]
class FinishingJob extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => FinishingType::class,
            'status' => FinishingJobStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * @return HasMany<QcRecord, $this>
     */
    public function qcRecords(): HasMany
    {
        return $this->hasMany(QcRecord::class);
    }

    /**
     * @return HasMany<Rework, $this>
     */
    public function reworks(): HasMany
    {
        return $this->hasMany(Rework::class);
    }

    /**
     * @return HasOne<Packing, $this>
     */
    public function packing(): HasOne
    {
        return $this->hasOne(Packing::class, 'order_item_id', 'order_item_id');
    }
}
