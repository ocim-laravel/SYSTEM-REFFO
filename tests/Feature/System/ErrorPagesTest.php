<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_renders_custom_404_page_for_unknown_route(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('404')
            ->assertSee('Halaman tidak ditemukan')
            ->assertSee('Kembali ke beranda');
    }

    #[Test]
    public function it_renders_custom_403_page_when_access_is_denied(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $this->actingAs($admin)
            ->get(route('system.settings.index'))
            ->assertForbidden()
            ->assertSee('403')
            ->assertSee('Akses ditolak')
            ->assertSee('Anda tidak memiliki izin untuk membuka halaman ini.');
    }

    #[Test]
    public function it_renders_custom_500_page(): void
    {
        $this->view('errors.500')
            ->assertSee('500')
            ->assertSee('Terjadi kesalahan pada server')
            ->assertSee('Silakan muat ulang halaman. Jika masalah berlanjut, hubungi administrator.');
    }

    #[Test]
    public function error_pages_do_not_depend_on_the_authenticated_layout(): void
    {
        $this->view('errors.403')
            ->assertDontSee('data-user-id')
            ->assertDontSee('class="app-shell"');

        $this->view('errors.404')
            ->assertDontSee('data-user-id')
            ->assertDontSee('class="app-shell"');

        $this->view('errors.500')
            ->assertDontSee('data-user-id')
            ->assertDontSee('class="app-shell"');
    }
}
