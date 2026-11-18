<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_logs_successful_login_to_audit_log_when_credentials_are_valid(): void
    {
        $user = User::factory()->forRole(UserRole::Admin)->create([
            'email' => 'admin@example.com',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        $response = $this->withHeader('User-Agent', 'ReffoAuditTest')
            ->post(route('login.attempt'), [
                'email' => 'admin@example.com',
                'password' => 'secret123',
            ]);

        $response->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login',
            'model_type' => User::class,
            'model_id' => $user->id,
            'ip' => '127.0.0.1',
            'user_agent' => 'ReffoAuditTest',
        ]);
    }

    #[Test]
    public function it_does_not_write_audit_log_when_login_fails(): void
    {
        User::factory()->forRole(UserRole::Admin)->create([
            'email' => 'admin@example.com',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        $this->post(route('login.attempt'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[Test]
    public function it_blocks_login_when_attempts_exceed_limit(): void
    {
        User::factory()->forRole(UserRole::Admin)->create([
            'email' => 'admin@example.com',
            'is_active' => true,
            'password' => 'secret123',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from(route('login'))->post(route('login.attempt'), [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak', session('errors')->first('email'));
    }
}
