<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DivisionCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->owner = User::factory()->forRole(UserRole::Owner)->create();
    }

    #[Test]
    public function it_creates_a_division_and_normalizes_the_code(): void
    {
        $this->actingAs($this->owner)
            ->post(route('system.divisions.store'), [
                'code' => 'ops',
                'name' => 'Operasional',
                'is_active' => '1',
            ])
            ->assertRedirect(route('system.divisions.index'));

        $this->assertDatabaseHas('divisions', [
            'code' => 'OPS',
            'name' => 'Operasional',
            'is_active' => 1,
        ]);
    }

    #[Test]
    public function it_rejects_a_duplicate_division_code(): void
    {
        Division::create(['code' => 'ADM', 'name' => 'Administrasi', 'is_active' => true]);

        $this->actingAs($this->owner)
            ->post(route('system.divisions.store'), [
                'code' => 'adm',
                'name' => 'Administrasi Lain',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, Division::query()->count());
    }

    #[Test]
    public function it_updates_an_existing_division(): void
    {
        $division = Division::create(['code' => 'PRD', 'name' => 'Produksi', 'is_active' => true]);

        $this->actingAs($this->owner)
            ->put(route('system.divisions.update', $division), [
                'code' => 'PRD',
                'name' => 'Produksi & Gudang',
                'is_active' => '0',
            ])
            ->assertRedirect(route('system.divisions.index'));

        $this->assertDatabaseHas('divisions', [
            'id' => $division->id,
            'name' => 'Produksi & Gudang',
            'is_active' => 0,
        ]);
    }

    #[Test]
    public function it_deletes_a_division(): void
    {
        $division = Division::create(['code' => 'LPN', 'name' => 'Lapangan', 'is_active' => true]);

        $this->actingAs($this->owner)
            ->delete(route('system.divisions.destroy', $division))
            ->assertRedirect(route('system.divisions.index'));

        $this->assertDatabaseMissing('divisions', ['id' => $division->id]);
    }

    #[Test]
    public function it_lists_divisions_on_the_index_page(): void
    {
        Division::create(['code' => 'KEU', 'name' => 'Keuangan', 'is_active' => true]);

        $this->actingAs($this->owner)
            ->get(route('system.divisions.index'))
            ->assertOk()
            ->assertSee('KEU')
            ->assertSee('Keuangan');
    }
}
