<?php

namespace App\Models;

use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\Priority;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Satu pekerjaan di dalam order; spesifikasi, status, dan riwayatnya sendiri
 * (MOD-02, BR-02).
 */
#[Fillable([
    'order_id',
    'seq',
    'title',
    'category',
    'specification',
    'qty',
    'unit',
    'unit_price',
    'total',
    'deadline_at',
    'priority',
    'status',
])]
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'specification' => 'array',
            'qty' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total' => 'decimal:2',
            'deadline_at' => 'datetime',
            'category' => ItemCategory::class,
            'priority' => Priority::class,
            'status' => ItemStatus::class,
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<ItemStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(ItemStatusHistory::class);
    }

    /**
     * @return HasMany<DesignJob, $this>
     */
    public function designJobs(): HasMany
    {
        return $this->hasMany(DesignJob::class);
    }

    /**
     * @return HasMany<ChangeRequest, $this>
     */
    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class);
    }

    /**
     * @return HasOne<PrintJob, $this>
     */
    public function printJob(): HasOne
    {
        return $this->hasOne(PrintJob::class);
    }

    /**
     * @return HasMany<FinishingJob, $this>
     */
    public function finishingJobs(): HasMany
    {
        return $this->hasMany(FinishingJob::class);
    }

    /**
     * @return HasMany<Rework, $this>
     */
    public function reworks(): HasMany
    {
        return $this->hasMany(Rework::class);
    }

    /**
     * @return HasMany<Packing, $this>
     */
    public function packings(): HasMany
    {
        return $this->hasMany(Packing::class);
    }
}
