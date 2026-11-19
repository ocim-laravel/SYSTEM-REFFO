<?php

namespace App\Models;

use App\Enums\ItemStatus;
use Database\Factories\ItemStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat perubahan status item (integritas #7, 05-data-model.md).
 */
#[Fillable(['order_item_id', 'from_status', 'to_status', 'changed_by', 'note'])]
class ItemStatusHistory extends Model
{
    /** @use HasFactory<ItemStatusHistoryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => ItemStatus::class,
            'to_status' => ItemStatus::class,
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
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
