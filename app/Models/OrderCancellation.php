<?php

namespace App\Models;

use Database\Factories\OrderCancellationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pembatalan order: biaya aktual dan nilai kesepakatan tercatat terpisah (BR-15).
 */
#[Fillable([
    'order_id',
    'reason',
    'actual_cost',
    'settlement_amount',
    'settlement_note',
    'decided_by',
    'decided_at',
])]
class OrderCancellation extends Model
{
    /** @use HasFactory<OrderCancellationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actual_cost' => 'decimal:2',
            'settlement_amount' => 'decimal:2',
            'decided_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
