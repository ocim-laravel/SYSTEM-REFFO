<?php

namespace Tests\Feature\Finishing;

use App\Enums\ItemStatus;
use App\Enums\UserRole;
use App\Models\FinishingJob;
use App\Models\User;
use App\Services\Production\FinishingService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FinishingQueueTest extends TestCase
{
    use FinishingTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_rejects_item_when_intake_requested_before_printing_is_done(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $item = $this->printedItem([
            'title' => 'Item Belum Cetak',
            'status' => ItemStatus::Printing,
        ]);

        try {
            app(FinishingService::class)->intake($item, $admin);
            $this->fail('BR-12 seharusnya menolak item yang belum printed.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('item', $exception->errors());
            $this->assertStringContainsString('BR-12', $exception->errors()['item'][0]);
        }

        $this->actingAs($admin)
            ->post(route('finishing.intake', $item))
            ->assertSessionHasErrors('item');

        $this->assertSame(ItemStatus::Printing, $item->fresh()->status);
        $this->assertDatabaseCount('finishing_jobs', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    #[Test]
    public function it_lists_printed_items_when_queue_page_is_opened(): void
    {
        $operator = User::factory()->forRole(UserRole::Production)->create();
        $printed = $this->printedItem(['title' => 'Item Siap Finishing']);
        $draft = $this->printedItem([
            'title' => 'Item Masih Draft',
            'status' => ItemStatus::Draft,
        ]);

        $this->actingAs($operator)
            ->get(route('finishing.index'))
            ->assertOk()
            ->assertSee('Item Siap Finishing')
            ->assertSee('Sudah Cetak')
            ->assertDontSee('Item Masih Draft');

        $this->assertTrue($printed->is($printed->fresh()));
        $this->assertSame(ItemStatus::Draft, $draft->fresh()->status);
    }

    #[Test]
    public function it_moves_item_to_finishing_and_writes_history_when_intake_succeeds(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $item = $this->printedItem(['title' => 'Item Intake Finishing']);

        $this->actingAs($admin)
            ->post(route('finishing.intake', $item))
            ->assertRedirect(route('finishing.show', $item));

        $job = FinishingJob::query()->where('order_item_id', $item->id)->firstOrFail();

        $this->assertSame(ItemStatus::Finishing, $item->fresh()->status);
        $this->assertSame('queued', $job->status->value);
        $this->assertNull($job->assigned_to);

        $this->assertDatabaseHas('item_status_histories', [
            'order_item_id' => $item->id,
            'from_status' => ItemStatus::Printed->value,
            'to_status' => ItemStatus::Finishing->value,
            'changed_by' => $admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'finishing.intake',
            'user_id' => $admin->id,
            'model_type' => FinishingJob::class,
            'model_id' => $job->id,
        ]);

        $this->actingAs($admin)
            ->get(route('finishing.index'))
            ->assertOk()
            ->assertSee('Item Intake Finishing');

        $this->assertDatabaseCount('finishing_jobs', 1);
    }
}
