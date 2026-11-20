<?php

namespace App\Models;

use App\Enums\PrintJobStatus;
use App\Enums\PrintQueueClass;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pekerjaan cetak per item (MOD-04).
 */
#[Fillable([
    'order_item_id',
    'queue_class',
    'status',
    'assigned_to',
    'assigned_by',
    'qty_planned',
    'qty_actual',
    'started_at',
    'finished_at',
])]
class PrintJob extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'queue_class' => PrintQueueClass::class,
            'status' => PrintJobStatus::class,
            'qty_planned' => 'decimal:2',
            'qty_actual' => 'decimal:2',
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
     * @return HasMany<PrintJobOperator, $this>
     */
    public function operators(): HasMany
    {
        return $this->hasMany(PrintJobOperator::class);
    }

    /**
     * @return HasMany<ReprintRequest, $this>
     */
    public function reprintRequests(): HasMany
    {
        return $this->hasMany(ReprintRequest::class);
    }

    public function activeOperator(): ?PrintJobOperator
    {
        /** @var PrintJobOperator|null $active */
        $active = $this->operators()->whereNull('ended_at')->latest('started_at')->first();

        return $active;
    }
}
