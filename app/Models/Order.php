<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\Priority;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Satu pesanan pelanggan, berisi banyak item (MOD-02, BR-01).
 */
#[Fillable([
    'code',
    'customer_id',
    'created_by',
    'validated_by',
    'validated_at',
    'status',
    'priority',
    'deadline_at',
    'subtotal',
    'discount',
    'tax',
    'total',
    'delivery_fee',
    'notes',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
            'deadline_at' => 'datetime',
            'status' => OrderStatus::class,
            'priority' => Priority::class,
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * @return HasOne<OrderCancellation, $this>
     */
    public function cancellation(): HasOne
    {
        return $this->hasOne(OrderCancellation::class);
    }
}
