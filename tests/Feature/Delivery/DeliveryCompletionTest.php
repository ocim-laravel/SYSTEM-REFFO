<?php

namespace Tests\Feature\Delivery;

use App\Enums\DeliveryStatus;
use App\Enums\DeliveryType;
use App\Enums\ItemStatus;
use App\Enums\OrderStatus;
use App\Models\AuditLog;
use App\Models\DeliveryProof;
use App\Models\OrderItem;
use App\Services\Delivery\DeliveryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeliveryCompletionTest extends TestCase
{
    use DeliveryTestHelpers;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Storage::fake('local');
    }

    #[Test]
    public function it_rejects_completion_without_proof_photo(): void
    {
        $admin = $this->adminUser();
        $field = $this->fieldUser();
        $order = $this->finishedOrder(1, ItemStatus::Packed);

        $delivery = app(DeliveryService::class)->schedule(
            $this->schedulePayload($order, $order->items()->pluck('id')->all(), DeliveryType::Delivery, [$field->id]),
            $admin,
        );

        $this->actingAs($admin)
            ->post(route('deliveries.complete', $delivery))
            ->assertSessionHasErrors('photo');

        $this->assertSame(DeliveryStatus::Scheduled, $delivery->fresh()->status);
        $this->assertDatabaseCount('delivery_proofs', 0);
    }

    #[Test]
    public function it_records_crew_time_and_photo_when_completed(): void
    {
        $admin = $this->adminUser();
        $field = $this->fieldUser();
        $order = $this->finishedOrder(3, ItemStatus::Packed);
        $itemIds = $order->items()->pluck('id')->all();

        $delivery = app(DeliveryService::class)->schedule(
            $this->schedulePayload($order, $itemIds, DeliveryType::Installation, [$field->id]),
            $admin,
        );

        $this->actingAs($field)
            ->post(route('deliveries.proof', $delivery), [
                'photo' => UploadedFile::fake()->create('bukti.jpg', 100, 'image/jpeg'),
                'note' => 'Terpasang di lokasi.',
            ])
            ->assertRedirect(route('deliveries.show', $delivery));

        $proof = DeliveryProof::query()->firstOrFail();

        $this->assertSame($field->id, $proof->uploaded_by);
        $this->assertSame($field->id, $proof->confirmed_by);
        $this->assertNotNull($proof->confirmed_at);
        Storage::disk('local')->assertExists($proof->photo_path);

        $this->actingAs($admin)
            ->post(route('deliveries.complete', $delivery))
            ->assertRedirect(route('deliveries.show', $delivery));

        $fresh = $delivery->fresh();

        $this->assertSame(DeliveryStatus::Completed, $fresh->status);
        $this->assertSame($admin->id, $fresh->completed_by);
        $this->assertNotNull($fresh->completed_at);
        $this->assertSame([$field->id], $fresh->crew);
        $this->assertSame(1, $fresh->proofs()->count());

        foreach ($itemIds as $itemId) {
            $this->assertSame(ItemStatus::Completed, $this->orderItem($itemId)->status);
        }

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertTrue(AuditLog::query()->where('action', 'delivery.completed')->exists());
    }

    #[Test]
    public function it_completes_pickup_without_crew_or_proof(): void
    {
        $admin = $this->adminUser();
        $order = $this->finishedOrder(1, ItemStatus::Packed, '0.00');
        $itemId = $order->items()->firstOrFail()->id;

        $delivery = app(DeliveryService::class)->schedule(
            $this->schedulePayload($order, [$itemId], DeliveryType::Pickup),
            $admin,
        );

        $this->actingAs($admin)
            ->post(route('deliveries.complete', $delivery))
            ->assertRedirect(route('deliveries.show', $delivery));

        $fresh = $delivery->fresh();

        $this->assertSame(DeliveryStatus::Completed, $fresh->status);
        $this->assertSame([], $fresh->crew);
        $this->assertSame(0, $fresh->proofs()->count());
        $this->assertSame(ItemStatus::Completed, $this->orderItem($itemId)->status);
    }

    #[Test]
    public function it_records_departed_and_arrived_progress(): void
    {
        $admin = $this->adminUser();
        $field = $this->fieldUser();
        $order = $this->finishedOrder(1, ItemStatus::Packed);
        $itemId = $order->items()->firstOrFail()->id;

        $delivery = app(DeliveryService::class)->schedule(
            $this->schedulePayload($order, [$itemId], DeliveryType::Installation, [$field->id]),
            $admin,
        );

        $this->actingAs($field)
            ->post(route('deliveries.progress', $delivery), ['stage' => 'departed', 'note' => 'Berangkat dari workshop'])
            ->assertRedirect(route('deliveries.show', $delivery));

        $fresh = $delivery->fresh();

        $this->assertSame(DeliveryStatus::InProgress, $fresh->status);
        $this->assertNotNull($fresh->departed_at);
        $this->assertNull($fresh->arrived_at);
        $this->assertSame(ItemStatus::Installing, $this->orderItem($itemId)->status);

        $this->actingAs($field)
            ->post(route('deliveries.progress', $delivery), ['stage' => 'arrived'])
            ->assertRedirect(route('deliveries.show', $delivery));

        $this->assertNotNull($delivery->fresh()->arrived_at);
    }

    #[Test]
    public function it_prints_field_work_summary(): void
    {
        $admin = $this->adminUser();
        $field = $this->fieldUser();
        $order = $this->finishedOrder(3, ItemStatus::Packed, '400000.00');

        $delivery = app(DeliveryService::class)->schedule(
            $this->schedulePayload($order, $order->items()->pluck('id')->all(), DeliveryType::Installation, [$field->id]),
            $admin,
        );

        $this->actingAs($admin)
            ->get(route('deliveries.print', $delivery))
            ->assertOk()
            ->assertSee('Ringkasan Pekerjaan Lapangan')
            ->assertSee($order->code)
            ->assertSee('400.000')
            ->assertSee('BR-14');
    }

    private function orderItem(int $id): OrderItem
    {
        return OrderItem::query()->findOrFail($id);
    }
}
