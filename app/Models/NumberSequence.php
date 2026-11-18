<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Penyetel nomor otomatis per prefiks dan tahun (MOD-10, PH-00).
 *
 * @property string $prefix
 * @property int $year
 * @property int $next_value
 */
#[Fillable(['prefix', 'year', 'next_value'])]
class NumberSequence extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'next_value' => 'integer',
        ];
    }
}
