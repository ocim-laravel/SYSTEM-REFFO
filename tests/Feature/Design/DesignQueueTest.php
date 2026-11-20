<?php

namespace Tests\Feature\Design;

use App\Enums\CustomerType;
use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\Priority;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\Customer\CustomerService;
use App\Services\Order\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DesignQueueTest extends TestCase
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
            'name' => 'PT Pelanggan Desain',
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

    #[Test]
    public function it_only_shows_queued_design_items_in_the_design_queue(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Antrean', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);

        $item = $order->items->first();
        $this->assertSame(ItemStatus::QueuedDesign, $item->status);

        // Item berstatus lain tidak boleh muncul.
        $otherOrder = $this->makeValidatedOrder($designer, [
            ['title' => 'Item ACC', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
            ['title' => 'Item Produksi', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $accItem = $otherOrder->items->first();
        $prodItem = $otherOrder->items->last();
        $accItem->update(['status' => ItemStatus::Acc]);
        $prodItem->update(['status' => ItemStatus::QueuedProduction]);

        // Draft item (order belum divalidasi) juga tidak muncul.
        $draftOrder = app(OrderService::class)->create([
            'customer_id' => $this->activeCustomer($designer)->id,
            'items' => [['title' => 'Item Draft', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000]],
        ], $designer);

        $response = $this->actingAs($designer)->get(route('design.queue'));

        $response->assertOk();
        $response->assertSee('Item Antrean');
        $response->assertDontSee('Item ACC');
        $response->assertDontSee('Item Produksi');
        $response->assertDontSee('Item Draft');
    }

    #[Test]
    public function it_orders_queue_by_priority_then_deadline_then_id(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->activeCustomer($designer);

        $deadlineFar = now()->addDays(10);
        $deadlineNear = now()->addDays(2);

        $order = app(OrderService::class)->create([
            'customer_id' => $customer->id,
            'items' => [
                ['title' => 'Urgent Item', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000, 'priority' => Priority::Urgent->value, 'deadline_at' => $deadlineFar],
                ['title' => 'Normal Far', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000, 'priority' => Priority::Normal->value, 'deadline_at' => $deadlineFar],
                ['title' => 'H1 Item', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000, 'priority' => Priority::H1->value, 'deadline_at' => $deadlineNear],
                ['title' => 'Normal Near', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000, 'priority' => Priority::Normal->value, 'deadline_at' => $deadlineNear],
            ],
        ], $designer);

        app(OrderService::class)->validate($order, $admin);

        $response = $this->actingAs($designer)->get(route('design.queue'));

        $response->assertOk();
        $content = $response->getContent();
        $posUrgent = strpos($content, 'Urgent Item');
        $posH1 = strpos($content, 'H1 Item');
        $posNormalNear = strpos($content, 'Normal Near');
        $posNormalFar = strpos($content, 'Normal Far');

        $this->assertNotFalse($posUrgent);
        $this->assertLessThan($posH1, $posUrgent);
        $this->assertLessThan($posNormalNear, $posH1);
        $this->assertLessThan($posNormalFar, $posNormalNear);
    }

    #[Test]
    public function it_allows_designer_admin_production_and_owner_to_view_the_queue(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        foreach ([UserRole::Designer, UserRole::Admin, UserRole::Production, UserRole::Owner] as $role) {
            $user = $role === UserRole::Designer
                ? $designer
                : User::factory()->forRole($role)->create();

            $this->actingAs($user)->get(route('design.queue'))->assertOk();
        }
    }

    #[Test]
    public function it_forbids_finance_and_hrd_from_viewing_the_queue(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        foreach ([UserRole::Finance, UserRole::Hrd, UserRole::Field] as $role) {
            $user = User::factory()->forRole($role)->create();

            $this->actingAs($user)->get(route('design.queue'))->assertForbidden();
        }
    }

    #[Test]
    public function it_does_not_change_item_status_when_viewing_the_queue(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Readonly', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $order->items->first();
        $historiesBefore = $item->statusHistories()->count();

        $this->actingAs($designer)->get(route('design.queue'))->assertOk();

        $this->assertSame(ItemStatus::QueuedDesign, $item->fresh()->status);
        $this->assertSame($historiesBefore, $item->fresh()->statusHistories()->count());
    }

    #[Test]
    public function it_shows_empty_state_message_when_queue_is_empty(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        $this->actingAs($designer)->get(route('design.queue'))
            ->assertOk()
            ->assertSee('Belum ada item dalam antrean desain.');
    }
}
