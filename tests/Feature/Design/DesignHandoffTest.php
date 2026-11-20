<?php

namespace Tests\Feature\Design;

use App\Enums\CustomerType;
use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Customer\CustomerService;
use App\Services\Design\DesignService;
use App\Services\Order\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DesignHandoffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function activeCustomer(User $actor): Customer
    {
        return app(CustomerService::class)->create([
            'name' => 'PT Pelanggan Serah Produksi',
            'type' => CustomerType::Company->value,
            'is_active' => true,
        ], $actor);
    }

    private function makeValidatedOrder(User $creator, array $items): Order
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->activeCustomer($creator);

        $order = app(OrderService::class)->create([
            'customer_id' => $customer->id,
            'items' => $items,
        ], $creator);

        app(OrderService::class)->validate($order, $admin);

        return $order->fresh()->load('items');
    }

    private function makeAccItem(User $designer, User $approver): OrderItem
    {
        Storage::fake('local');

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Serah Produksi', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $order->items->first();

        app(DesignService::class)->claim($item, $designer);

        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('final-v1.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $this->actingAs($designer)->post(route('design.submit', $item))->assertRedirect();
        $this->actingAs($approver)->post(route('design.approve', $item), [
            'method' => 'internal',
        ])->assertRedirect();

        return $item->fresh();
    }

    #[Test]
    public function it_hands_acc_item_to_production_with_status_history_and_audit_log(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $item = $this->makeAccItem($designer, $admin);
        $this->assertSame(ItemStatus::Acc, $item->status);

        $result = app(DesignService::class)->handoffToProduction($item, $admin);

        $this->assertSame(ItemStatus::QueuedProduction, $result->status);
        $this->assertSame(ItemStatus::QueuedProduction, $item->fresh()->status);

        $history = $item->fresh()->statusHistories()->latest('id')->firstOrFail();
        $this->assertSame(ItemStatus::Acc, $history->from_status);
        $this->assertSame(ItemStatus::QueuedProduction, $history->to_status);
        $this->assertSame($admin->id, $history->changed_by);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'design.handed_off_to_production',
            'user_id' => $admin->id,
            'model_type' => OrderItem::class,
            'model_id' => $item->id,
        ]);
    }

    #[Test]
    public function it_rejects_handoff_when_item_is_not_acc(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Belum ACC', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $order->items->first();

        try {
            app(DesignService::class)->handoffToProduction($item, $admin);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('item', $e->errors());
        }

        $this->assertSame(ItemStatus::QueuedDesign, $item->fresh()->status);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'design.handed_off_to_production',
        ]);
    }

    #[Test]
    public function it_forbids_non_admin_from_handing_item_to_production(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $item = $this->makeAccItem($designer, $admin);

        $this->expectException(AuthorizationException::class);

        app(DesignService::class)->handoffToProduction($item, $designer);
    }

    #[Test]
    public function it_rejects_second_handoff_to_production(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $item = $this->makeAccItem($designer, $admin);
        app(DesignService::class)->handoffToProduction($item, $admin);

        try {
            app(DesignService::class)->handoffToProduction($item->fresh(), $admin);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('item', $e->errors());
        }

        $this->assertSame(ItemStatus::QueuedProduction, $item->fresh()->status);
        $this->assertSame(1, $item->fresh()
            ->statusHistories()
            ->where('to_status', ItemStatus::QueuedProduction->value)
            ->count());
    }

    #[Test]
    public function it_hands_acc_item_to_production_over_http_and_records_audit_log(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $item = $this->makeAccItem($designer, $admin);
        $this->assertSame(ItemStatus::Acc, $item->status);

        $response = $this->actingAs($admin)->post(route('design.handoff', $item));

        $response->assertRedirect(route('design.upload', $item));
        $response->assertSessionHas('status');

        $this->assertSame(ItemStatus::QueuedProduction, $item->fresh()->status);

        $history = $item->fresh()->statusHistories()->latest('id')->firstOrFail();
        $this->assertSame(ItemStatus::Acc, $history->from_status);
        $this->assertSame(ItemStatus::QueuedProduction, $history->to_status);
        $this->assertSame($admin->id, $history->changed_by);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'design.handed_off_to_production',
            'user_id' => $admin->id,
            'model_type' => OrderItem::class,
            'model_id' => $item->id,
        ]);
    }

    #[Test]
    public function it_rejects_http_handoff_when_item_is_not_acc(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Belum ACC HTTP', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $order->items->first();

        $response = $this->actingAs($admin)->post(route('design.handoff', $item));

        $response->assertRedirect();
        $response->assertSessionHasErrors('item');

        $this->assertSame(ItemStatus::QueuedDesign, $item->fresh()->status);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'design.handed_off_to_production',
        ]);
    }

    #[Test]
    public function it_rejects_second_http_handoff_to_production(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $item = $this->makeAccItem($designer, $admin);

        $this->actingAs($admin)->post(route('design.handoff', $item))->assertRedirect();

        $response = $this->actingAs($admin)->post(route('design.handoff', $item));

        $response->assertRedirect();
        $response->assertSessionHasErrors('item');

        $this->assertSame(ItemStatus::QueuedProduction, $item->fresh()->status);
        $this->assertSame(1, $item->fresh()
            ->statusHistories()
            ->where('to_status', ItemStatus::QueuedProduction->value)
            ->count());
    }

    #[Test]
    public function it_forbids_non_admin_from_handing_off_over_http(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $item = $this->makeAccItem($designer, $admin);

        $this->actingAs($designer)->post(route('design.handoff', $item))
            ->assertForbidden();

        $this->assertSame(ItemStatus::Acc, $item->fresh()->status);
        $this->assertDatabaseMissing('audit_logs', [
            'action' => 'design.handed_off_to_production',
        ]);
    }

    #[Test]
    public function it_only_shows_handoff_button_to_admin_on_acc_item(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $item = $this->makeAccItem($designer, $admin);

        $this->actingAs($admin)->get(route('design.upload', $item))
            ->assertOk()
            ->assertSee('Serahkan ke Produksi')
            ->assertSee(route('design.handoff', $item), false);

        $this->actingAs($designer)->get(route('design.upload', $item))
            ->assertOk()
            ->assertDontSee('Serahkan ke Produksi');
    }
}
