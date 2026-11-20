<?php

namespace App\Models;

use App\Enums\DesignJobStatus;
use Database\Factories\DesignJobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pekerjaan desain per item (MOD-03, AC-03-2).
 */
#[Fillable([
    'order_item_id',
    'designer_id',
    'assigned_by',
    'assigned_at',
    'started_at',
    'finished_at',
    'status',
    'revision_cycle_used',
])]
class DesignJob extends Model
{
    /** @use HasFactory<DesignJobFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'status' => DesignJobStatus::class,
            'revision_cycle_used' => 'integer',
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
    public function designer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'designer_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * @return HasMany<DesignFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(DesignFile::class);
    }

    /**
     * @return HasMany<DesignRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(DesignRevision::class);
    }

    /**
     * @return HasMany<DesignApproval, $this>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(DesignApproval::class);
    }
}
