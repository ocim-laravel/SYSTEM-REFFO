<?php

namespace App\Models;

use Database\Factories\DeliveryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item order yang mengikuti satu jadwal lapangan (MOD-06).
 */
#[Fillable([
    'delivery_id',
    'order_item_id',
])]
class DeliveryItem extends Model
{
    /** @use HasFactory<DeliveryItemFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Delivery, $this>
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(Delivery::class);
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
