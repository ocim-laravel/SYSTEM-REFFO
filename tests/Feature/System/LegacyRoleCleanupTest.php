<?php

namespace Tests\Feature\System;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LegacyRoleCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_removes_the_legacy_role_column_from_users(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'role'));
    }

    #[Test]
    public function it_reads_the_primary_role_from_spatie_only(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $this->assertSame(UserRole::Admin, $admin->roleEnum());
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->hasRole(UserRole::Production->value));
    }

    #[Test]
    public function it_rejects_every_role_on_the_legacy_operator_dashboard(): void
    {
        $user = User::factory()->forRole(UserRole::Owner)->create();

        $this->actingAs($user)
            ->get(route('operator.dashboard'))
            ->assertForbidden();
    }
}
