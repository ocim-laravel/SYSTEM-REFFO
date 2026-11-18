<?php

namespace App\Services\System;

use App\Models\Division;
use Illuminate\Support\Facades\DB;

/**
 * Business logic CRUD divisi (MOD-10).
 */
class DivisionService
{
    /**
     * @param  array{code: string, name: string, is_active?: bool}  $data
     */
    public function create(array $data): Division
    {
        return DB::transaction(fn (): Division => Division::create([
            'code' => $data['code'],
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? true,
        ]));
    }

    /**
     * @param  array{code: string, name: string, is_active?: bool}  $data
     */
    public function update(Division $division, array $data): Division
    {
        DB::transaction(fn () => $division->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? $division->is_active,
        ]));

        return $division->refresh();
    }

    public function delete(Division $division): void
    {
        DB::transaction(fn () => $division->delete());
    }
}
