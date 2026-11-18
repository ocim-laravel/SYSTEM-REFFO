<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

/**
 * Divisi awal sesuai peta modul (01-scope) dan role sistem (MOD-10).
 */
class DivisionSeeder extends Seeder
{
    /**
     * @var array<int, array{code: string, name: string}>
     */
    public const DIVISIONS = [
        ['code' => 'ADM', 'name' => 'Administrasi'],
        ['code' => 'DSN', 'name' => 'Desain'],
        ['code' => 'PRD', 'name' => 'Produksi'],
        ['code' => 'LPN', 'name' => 'Lapangan'],
        ['code' => 'KEU', 'name' => 'Keuangan'],
        ['code' => 'HRD', 'name' => 'HRD'],
    ];

    public function run(): void
    {
        foreach (self::DIVISIONS as $division) {
            Division::updateOrCreate(
                ['code' => $division['code']],
                ['name' => $division['name'], 'is_active' => true],
            );
        }
    }
}
