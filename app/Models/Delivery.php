<?php

namespace App\Models;

use App\Enums\DeliveryStatus;
use App\Enums\DeliveryType;
use Database\Factories\DeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Satu jadwal kerja lapangan untuk satu order (MOD-06, BR-13, BR-14).
 */
#[Fillable([
    'order_id',
    'type',
    'scheduled_at',
    'scheduled_by',
    'crew',
    'status',
    'address',
    'fee',
    'departed_at',
    'arrived_at',
    'completed_by',
    'completed_at',
    'notes',
])]
class Delivery extends Model
{
    /** @use HasFactory<DeliveryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DeliveryType::class,
            'status' => DeliveryStatus::class,
            'crew' => 'array',
            'scheduled_at' => 'datetime',
            'departed_at' => 'datetime',
            'arrived_at' => 'datetime',
            'completed_at' => 'datetime',
            'fee' => 'decimal:2',
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
    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * @return HasMany<DeliveryItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DeliveryItem::class);
    }

    /**
     * @return HasMany<DeliveryProof, $this>
     */
    public function proofs(): HasMany
    {
        return $this->hasMany(DeliveryProof::class);
    }

    /**
     * Petugas lapangan yang ditugaskan (crew disimpan sebagai JSON id).
     *
     * @return Collection<int, User>
     */
    public function crewMembers(): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $this->crew ?? [])));

        if ($ids === []) {
            return collect();
        }

        return User::query()->whereIn('id', $ids)->get()->sortBy(
            fn (User $user): int => array_search($user->id, $ids, true),
        )->values();
    }
}
