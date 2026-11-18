<?php

namespace Tests\Feature\System;

use App\Enums\OverridableRule;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\System\OverrideService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OwnerOverrideTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $target;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->owner = User::factory()->forRole(UserRole::Owner)->create();
        $this->target = User::factory()->forRole(UserRole::Admin)->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'rule' => OverridableRule::AccessPermission->value,
            'target_type' => User::class,
            'target_id' => $this->target->id,
            'before' => ['is_active' => false],
            'after' => ['is_active' => true],
            'reason' => 'Kesepakatan khusus dengan pelanggan.',
        ], $overrides);
    }

    #[Test]
    public function it_rejects_override_when_reason_is_missing(): void
    {
        $payload = $this->payload();
        unset($payload['reason']);

        $this->actingAs($this->owner)
            ->postJson(route('system.overrides.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[Test]
    public function it_rejects_override_when_reason_is_blank(): void
    {
        $this->actingAs($this->owner)
            ->postJson(route('system.overrides.store'), $this->payload(['reason' => '   ']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[Test]
    public function it_records_override_with_before_after_and_reason_when_owner_provides_reason(): void
    {
        $this->actingAs($this->owner)
            ->post(route('system.overrides.store'), $this->payload())
            ->assertRedirect(route('system.overrides.index'));

        $log = AuditLog::query()->firstOrFail();

        $this->assertSame('override.access_permission', $log->action);
        $this->assertSame('Kesepakatan khusus dengan pelanggan.', $log->reason);
        $this->assertSame(['is_active' => false], $log->before);
        $this->assertSame(['is_active' => true], $log->after);
        $this->assertSame($this->owner->id, $log->user_id);
        $this->assertSame(User::class, $log->model_type);
        $this->assertSame($this->target->id, $log->model_id);
    }

    #[Test]
    public function it_forbids_override_when_actor_is_not_owner(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $this->actingAs($admin)
            ->get(route('system.overrides.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('system.overrides.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[Test]
    public function it_detects_a_recorded_override_when_checking_a_rule(): void
    {
        /** @var OverrideService $service */
        $service = app(OverrideService::class);

        $this->assertFalse($service->hasOverride(OverridableRule::AccessPermission, User::class, $this->target->id));

        $service->override($this->owner, [
            'rule' => OverridableRule::AccessPermission,
            'target_type' => User::class,
            'target_id' => $this->target->id,
            'before' => ['is_active' => false],
            'after' => ['is_active' => true],
            'reason' => 'Override dicatat untuk keperluan uji.',
        ]);

        $this->assertTrue($service->hasOverride(OverridableRule::AccessPermission, User::class, $this->target->id));
        $this->assertFalse($service->hasOverride(OverridableRule::AccessPermission, User::class, 99999));
        $this->assertFalse($service->hasOverride(OverridableRule::WorkCalendar, User::class, $this->target->id));
    }

    #[Test]
    public function it_sanitizes_sensitive_fields_from_the_audit_snapshot(): void
    {
        $this->actingAs($this->owner)
            ->post(route('system.overrides.store'), $this->payload([
                'before' => ['is_active' => false, 'password' => 'rahasia', 'token' => 'abc'],
                'after' => ['is_active' => true],
            ]))
            ->assertRedirect(route('system.overrides.index'));

        $log = AuditLog::query()->firstOrFail();

        $this->assertSame(['is_active' => false], $log->before);
        $this->assertArrayNotHasKey('password', $log->before);
        $this->assertArrayNotHasKey('token', $log->before);
    }
}
