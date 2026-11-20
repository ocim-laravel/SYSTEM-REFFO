<?php

namespace Tests\Feature\Finishing;

use App\Enums\ItemStatus;
use App\Enums\QcResult;
use App\Enums\RedoStage;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Packing;
use App\Models\Rework;
use App\Models\User;
use App\Services\Production\FinishingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FinishingPackingTest extends TestCase
{
    use FinishingTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_rejects_packing_when_qc_has_not_passed(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operator = User::factory()->forRole(UserRole::Production)->create();
        $item = $this->printedItem();
        $job = $this->intakeItem($item, $admin);
        $this->completeJob($job, $admin);

        $this->actingAs($operator)
            ->post(route('finishing.packing.start', $item))
            ->assertSessionHasErrors('packing');

        $this->assertDatabaseCount('packings', 0);
        $this->assertSame(ItemStatus::Finishing, $item->fresh()->status);
    }

    #[Test]
    public function it_rejects_packing_when_rework_is_done_but_qc_has_not_been_repeated(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operator = User::factory()->forRole(UserRole::Production)->create();
        $item = $this->printedItem();
        $job = $this->intakeItem($item, $admin);
        $this->completeJob($job, $admin);

        app(FinishingService::class)->recordQc(
            $job,
            QcResult::Fail,
            'Jahitan lepas',
            $admin,
            RedoStage::Finishing,
        );
        $rework = Rework::query()->where('order_item_id', $item->id)->firstOrFail();
        app(FinishingService::class)->completeRework($rework, $admin);

        // Status kembali `Qc`, tetapi QC ulang belum lulus.
        $this->assertSame(ItemStatus::Qc, $item->fresh()->status);

        $this->actingAs($operator)
            ->post(route('finishing.packing.start', $item))
            ->assertSessionHasErrors('packing');

        $this->assertDatabaseCount('packings', 0);
    }

    #[Test]
    public function it_moves_item_to_packed_when_qc_passed_and_packing_is_completed(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operator = User::factory()->forRole(UserRole::Production)->create();
        $item = $this->printedItem();
        $job = $this->intakeItem($item, $admin);
        $this->completeJob($job, $admin);

        $this->actingAs($admin)
            ->post(route('finishing.qc', $item), ['result' => 'pass'])
            ->assertRedirect();

        $this->actingAs($operator)
            ->post(route('finishing.packing.start', $item))
            ->assertRedirect(route('finishing.show', $item));

        $packing = Packing::query()->where('order_item_id', $item->id)->firstOrFail();

        $this->assertSame(ItemStatus::Packing, $item->fresh()->status);
        $this->assertSame('in_progress', $packing->status->value);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'finishing.packing_started',
            'user_id' => $operator->id,
            'model_type' => Packing::class,
            'model_id' => $packing->id,
        ]);

        $this->actingAs($operator)
            ->post(route('finishing.packing.complete', [$item, $packing]), ['note' => 'Aman, terbungkus bubble wrap'])
            ->assertRedirect(route('finishing.show', $item));

        $this->assertSame(ItemStatus::Packed, $item->fresh()->status);
        $this->assertSame('packed', $packing->fresh()->status->value);
        $this->assertNotNull($packing->fresh()->packed_at);
        $this->assertSame('Aman, terbungkus bubble wrap', $packing->fresh()->note);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'finishing.packed',
            'user_id' => $operator->id,
            'model_type' => Packing::class,
            'model_id' => $packing->id,
        ]);

        $this->assertTrue(AuditLog::query()->where('action', 'finishing.packed')->exists());
    }

    #[Test]
    public function it_shows_chronological_history_with_actors_when_full_cycle_is_done(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operator = User::factory()->forRole(UserRole::Production)->create();
        $item = $this->printedItem(['title' => 'Item Riwayat Lengkap']);
        $service = app(FinishingService::class);
        $job = $this->intakeItem($item, $admin);
        $this->completeJob($job, $admin);

        $service->recordQc($job, QcResult::Fail, 'Jahitan lepas', $admin, RedoStage::Finishing);
        $rework = Rework::query()->where('order_item_id', $item->id)->firstOrFail();
        $service->completeRework($rework, $admin);
        $service->recordQc($job, QcResult::Pass, 'Sudah rapi', $admin);
        $service->startPacking($item, $operator);

        $packing = Packing::query()->where('order_item_id', $item->id)->firstOrFail();
        $service->completePacking($packing, 'Siap dikirim', $operator);

        $history = $service->history($item);

        $phases = $history
            ->whereIn('phase', ['finishing', 'qc', 'rework', 'packing'])
            ->pluck('phase')
            ->unique()
            ->values()
            ->all();

        $this->assertSame(['finishing', 'qc', 'rework', 'packing'], $phases);

        $actors = $history->pluck('actor')->implode('|');
        $this->assertStringContainsString($admin->name, $actors);
        $this->assertStringContainsString($operator->name, $actors);

        $this->actingAs($admin)
            ->get(route('finishing.show', $item))
            ->assertOk()
            ->assertSee('Item Riwayat Lengkap')
            ->assertSee('QC: Lulus')
            ->assertSee('Rework:')
            ->assertSee('Packing:');
    }
}
