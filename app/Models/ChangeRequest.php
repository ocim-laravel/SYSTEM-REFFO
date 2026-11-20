<?php

namespace App\Models;

use App\Enums\ChangeRequestStatus;
use Database\Factories\ChangeRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Change Request setelah ACC (MOD-03, AC-03-6, BR-05).
 */
#[Fillable([
    'order_item_id',
    'design_file_id',
    'reason',
    'cost_impact',
    'deadline_impact',
    'status',
    'approved_by',
    'approved_at',
    'applied_at',
])]
class ChangeRequest extends Model
{
    /** @use HasFactory<ChangeRequestFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cost_impact' => 'decimal:2',
            'deadline_impact' => 'datetime',
            'status' => ChangeRequestStatus::class,
            'approved_at' => 'datetime',
            'applied_at' => 'datetime',
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
     * @return BelongsTo<DesignFile, $this>
     */
    public function designFile(): BelongsTo
    {
        return $this->belongsTo(DesignFile::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
