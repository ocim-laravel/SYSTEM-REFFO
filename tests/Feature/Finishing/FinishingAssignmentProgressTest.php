<?php

namespace Tests\Feature\Finishing;

use App\Enums\ItemStatus;
use App\Enums\UserRole;
use App\Models\FinishingJob;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FinishingAssignmentProgressTest extends TestCase
{
    use FinishingTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_assigns_job_when_admin_shares_work_with_operator(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operator = User::factory()->forRole(UserRole::Production)->create();
        $item = $this->printedItem();
        $job = $this->intakeItem($item, $admin);

        $this->actingAs($admin)
            ->post(route('finishing.assign', $item), ['operator_id' => $operator->id])
            ->assertRedirect(route('finishing.show', $item));

        $this->assertSame($operator->id, $job->fresh()->assigned_to);
        $this->assertSame($admin->id, $job->fresh()->assigned_by);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'finishing.assigned',
            'user_id' => $admin->id,
            'model_type' => FinishingJob::class,
            'model_id' => $job->id,
        ]);
    }

    #[Test]
    public function it_forbids_regular_operator_when_assigning_work_to_another_operator(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operatorA = User::factory()->forRole(UserRole::Production)->create();
        $operatorB = User::factory()->forRole(UserRole::Production)->create();
        $item = $this->printedItem();
        $job = $this->intakeItem($item, $admin);

        $this->actingAs($operatorA)
            ->post(route('finishing.assign', $item), ['operator_id' => $operatorB->id])
            ->assertForbidden();

        $this->assertNull($job->fresh()->assigned_to);
        $this->assertDatabaseCount('audit_logs', 1);
    }

    #[Test]
    public function it_claims_unheld_job_when_operator_is_authorized(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operator = User::factory()->forRole(UserRole::Production)->create();
        $other = User::factory()->forRole(UserRole::Production)->create();
        $item = $this->printedItem();
        $job = $this->intakeItem($item, $admin);

        $this->actingAs($operator)
            ->post(route('finishing.claim', $item))
            ->assertRedirect(route('finishing.show', $item));

        $this->assertSame($operator->id, $job->fresh()->assigned_to);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'finishing.claimed',
            'user_id' => $operator->id,
            'model_type' => FinishingJob::class,
            'model_id' => $job->id,
        ]);

        $this->actingAs($other)
            ->post(route('finishing.claim', $item))
            ->assertSessionHasErrors('operator');

        $this->assertSame($operator->id, $job->fresh()->assigned_to);
    }

    #[Test]
    public function it_records_progress_when_job_is_held_by_operator(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operator = User::factory()->forRole(UserRole::Production)->create();
        $item = $this->printedItem();
        $job = $this->intakeItem($item, $admin);

        $this->actingAs($operator)
            ->post(route('finishing.claim', $item))
            ->assertRedirect();

        $this->actingAs($operator)
            ->post(route('finishing.progress', $item), [
                'finished_at' => now()->toDateTimeString(),
                'progress_note' => 'Potong dan laminasi selesai.',
            ])
            ->assertRedirect(route('finishing.show', $item));

        $this->assertSame('completed', $job->fresh()->status->value);
        $this->assertSame('Potong dan laminasi selesai.', $job->fresh()->progress_note);
        $this->assertSame(ItemStatus::Finishing, $item->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'finishing.progress_recorded',
            'user_id' => $operator->id,
            'model_type' => FinishingJob::class,
            'model_id' => $job->id,
        ]);
    }
}
