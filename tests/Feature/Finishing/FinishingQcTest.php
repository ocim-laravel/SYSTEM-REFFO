<?php

namespace Tests\Feature\Finishing;

use App\Enums\ItemStatus;
use App\Enums\QcResult;
use App\Enums\RedoStage;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\QcRecord;
use App\Models\Rework;
use App\Models\User;
use App\Services\Production\FinishingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FinishingQcTest extends TestCase
{
    use FinishingTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_rejects_qc_fail_when_notes_are_missing(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $item = $this->printedItem();
        $job = $this->intakeItem($item, $admin);
        $this->completeJob($job, $admin);

        $this->actingAs($admin)
            ->post(route('finishing.qc', $item), ['result' => 'fail'])
            ->assertSessionHasErrors('notes');

        $this->assertDatabaseCount('qc_records', 0);
        $this->assertDatabaseCount('reworks', 0);
        $this->assertSame(ItemStatus::Finishing, $item->fresh()->status);
    }

    #[Test]
    public function it_rejects_qc_fail_when_notes_are_whitespace(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $item = $this->printedItem();
        $job = $this->intakeItem($item, $admin);
        $this->completeJob($job, $admin);

        $this->actingAs($admin)
            ->post(route('finishing.qc', $item), [
                'result' => 'fail',
                'notes' => '    ',
            ])
            ->assertSessionHasErrors('notes');

        try {
            app(FinishingService::class)->recordQc($job, QcResult::Fail, '   ', $admin);
            $this->fail('Catatan kosong seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Catatan wajib diisi untuk hasil QC gagal.',
                $exception->errors()['notes'][0],
            );
        }

        $this->assertDatabaseCount('qc_records', 0);
    }

    #[Test]
    public function it_moves_item_to_rework_and_records_history_when_qc_fails(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $item = $this->printedItem();
        $job = $this->intakeItem($item, $admin);
        $this->completeJob($job, $admin);

        $this->actingAs($admin)
            ->post(route('finishing.qc', $item), [
                'result' => 'fail',
                'notes' => 'Warna tidak sesuai contoh',
                'redo_stage' => 'finishing',
            ])
            ->assertRedirect(route('finishing.show', $item));

        $qc = QcRecord::query()->where('finishing_job_id', $job->id)->firstOrFail();
        $rework = Rework::query()->where('order_item_id', $item->id)->firstOrFail();

        $this->assertSame(QcResult::Fail, $qc->result);
        $this->assertSame('Warna tidak sesuai contoh', $qc->notes);
        $this->assertSame($admin->id, $qc->checked_by);
        $this->assertNotNull($qc->checked_at);

        $this->assertSame(RedoStage::Finishing, $rework->redo_stage);
        $this->assertSame('pending', $rework->status->value);
        $this->assertSame(ItemStatus::Rework, $item->fresh()->status);

        $this->assertDatabaseHas('item_status_histories', [
            'order_item_id' => $item->id,
            'to_status' => ItemStatus::Rework->value,
            'changed_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'finishing.qc_recorded',
            'user_id' => $admin->id,
            'model_type' => QcRecord::class,
            'model_id' => $qc->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'finishing.rework_started',
            'model_type' => Rework::class,
            'model_id' => $rework->id,
        ]);

        $this->assertTrue(AuditLog::query()->where('action', 'finishing.qc_recorded')->exists());
    }

    #[Test]
    public function it_repeats_qc_until_pass_when_rework_is_completed(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $item = $this->printedItem();
        $job = $this->intakeItem($item, $admin);
        $this->completeJob($job, $admin);

        $this->actingAs($admin)->post(route('finishing.qc', $item), [
            'result' => 'fail',
            'notes' => 'Lipatan belum rapi',
            'redo_stage' => 'finishing',
        ])->assertRedirect();

        $rework = Rework::query()->where('order_item_id', $item->id)->firstOrFail();

        $this->actingAs($admin)->post(route('finishing.rework.start', [$item, $rework]))->assertRedirect();
        $this->actingAs($admin)->post(route('finishing.rework.complete', [$item, $rework]))->assertRedirect();

        $this->assertSame('done', $rework->fresh()->status->value);
        $this->assertSame($admin->id, $rework->fresh()->done_by);
        $this->assertSame(ItemStatus::Qc, $item->fresh()->status);

        $this->actingAs($admin)->post(route('finishing.qc', $item), [
            'result' => 'pass',
            'notes' => 'Sudah sesuai standar',
        ])->assertRedirect(route('finishing.show', $item));

        $this->assertSame(2, QcRecord::query()->where('finishing_job_id', $job->id)->count());
        $this->assertSame(
            QcResult::Pass,
            QcRecord::query()->where('finishing_job_id', $job->id)->latest('id')->firstOrFail()->result,
        );
        $this->assertSame(ItemStatus::Qc, $item->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'finishing.rework_completed',
            'model_type' => Rework::class,
            'model_id' => $rework->id,
        ]);
    }

    #[Test]
    public function it_requires_approved_reprint_when_rework_redo_stage_is_cetak(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $item = $this->printedItem();
        $job = $this->intakeItem($item, $admin);
        $this->completeJob($job, $admin);

        app(FinishingService::class)->recordQc(
            $job,
            QcResult::Fail,
            'Hasil cetak buram',
            $admin,
            RedoStage::Cetak,
        );

        $rework = Rework::query()->where('order_item_id', $item->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('finishing.rework.complete', [$item, $rework]))
            ->assertSessionHasErrors('rework');

        $this->assertSame(ItemStatus::Rework, $item->fresh()->status);

        $this->approvedReprint($item, $admin);

        $this->actingAs($admin)
            ->post(route('finishing.rework.complete', [$item, $rework]))
            ->assertRedirect(route('finishing.show', $item));

        $this->assertSame(ItemStatus::Qc, $item->fresh()->status);
        $this->assertSame('done', $rework->fresh()->status->value);
    }
}
