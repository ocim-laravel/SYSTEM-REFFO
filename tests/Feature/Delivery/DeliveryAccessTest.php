<?php

namespace Tests\Feature\Delivery;

use App\Enums\DeliveryType;
use App\Enums\ItemStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Services\Delivery\DeliveryService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeliveryAccessTest extends TestCase
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
    public function it_forbids_unassigned_field_user_from_recording_progress(): void
    {
        $field = $this->fieldUser();
        $otherField = User::factory()->forRole(UserRole::Field)->create();
        $order = $this->finishedOrder(1, ItemStatus::Packed);

        $delivery = app(DeliveryService::class)->schedule(
            $this->schedulePayload($order, $order->items()->pluck('id')->all(), DeliveryType::Delivery, [$field->id]),
            $this->adminUser(),
        );

        $this->actingAs($otherField)
            ->post(route('deliveries.progress', $delivery), ['stage' => 'departed'])
            ->assertForbidden();
    }

    #[Test]
    public function it_forbids_unassigned_field_user_from_uploading_proof(): void
    {
        $field = $this->fieldUser();
        $otherField = User::factory()->forRole(UserRole::Field)->create();
        $order = $this->finishedOrder(1, ItemStatus::Packed);

        $delivery = app(DeliveryService::class)->schedule(
            $this->schedulePayload($order, $order->items()->pluck('id')->all(), DeliveryType::Delivery, [$field->id]),
            $this->adminUser(),
        );

        $this->actingAs($otherField)
            ->post(route('deliveries.proof', $delivery), ['photo' => UploadedFile::fake()->create('bukti.png', 100, 'image/png')])
            ->assertForbidden();

        $this->assertDatabaseCount('delivery_proofs', 0);
    }

    #[Test]
    public function it_forbids_designer_from_opening_delivery_list(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        $this->actingAs($designer)
            ->get(route('deliveries.index'))
            ->assertForbidden();
    }
}
