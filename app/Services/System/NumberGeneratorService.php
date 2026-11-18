<?php

namespace App\Services\System;

use App\Enums\NumberPrefix;
use App\Models\NumberSequence;
use Illuminate\Support\Facades\DB;

/**
 * Penomoran otomatis CUS-, ORD-, ITM- (MOD-10, PH-00).
 *
 * Nomor diambil dari satu baris Sequencer per prefiks/tahun dengan lock,
 * sehingga dua transaksi bersamaan tidak mendapat nomor yang sama.
 */
class NumberGeneratorService
{
    public function nextCustomer(?int $year = null): string
    {
        return $this->next(NumberPrefix::Customer, $year);
    }

    public function nextOrder(?int $year = null): string
    {
        return $this->next(NumberPrefix::Order, $year);
    }

    public function nextItem(?int $year = null): string
    {
        return $this->next(NumberPrefix::Item, $year);
    }

    public function next(NumberPrefix $prefix, ?int $year = null): string
    {
        $year ??= now()->year;

        return DB::transaction(function () use ($prefix, $year): string {
            $sequence = NumberSequence::query()->firstOrCreate(
                ['prefix' => $prefix->value, 'year' => $year],
                ['next_value' => 1],
            );

            /** @var NumberSequence $locked */
            $locked = NumberSequence::query()->whereKey($sequence->id)->lockForUpdate()->firstOrFail();
            $value = $locked->next_value;
            $locked->increment('next_value');

            return sprintf('%s-%d-%04d', $prefix->value, $year, $value);
        });
    }
}
