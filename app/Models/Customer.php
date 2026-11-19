<?php

namespace App\Models;

use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Data pelanggan — satu sumber data untuk seluruh modul (MOD-01, AC-01-1).
 */
#[Fillable([
    'code',
    'name',
    'type',
    'phone',
    'whatsapp',
    'email',
    'address',
    'notes',
    'is_active',
    'created_by',
])]
class Customer extends Model
{
    protected function casts(): array
    {
        return [
            'type' => CustomerType::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Hanya pelanggan aktif — dipakai saat memilih pelanggan untuk order baru
     * (MOD-01, AC-01-2).
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
