<?php

namespace Tests\Feature\Design;

use App\Enums\CustomerType;
use App\Enums\DesignJobStatus;
use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DesignJob;
use App\Models\Order;
use App\Models\User;
use App\Services\Customer\CustomerService;
use App\Services\Design\DesignService;
use App\Services\Order\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DesignClaimTest extends TestCase
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
    public function it_creates_design_job_and_moves_item_to_designing_when_designer_claims(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Claim', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);

        $item = $order->items->first();

        $response = $this->actingAs($designer)->post(route('design.claim', $item));

        $response->assertRedirect(route('design.queue'));

        $this->assertSame(ItemStatus::Designing, $item->fresh()->status);

        $job = DesignJob::where('order_item_id', $item->id)->firstOrFail();
        $this->assertSame($designer->id, $job->designer_id);
        $this->assertSame($designer->id, $job->assigned_by);
        $this->assertNotNull($job->assigned_at);
        $this->assertSame(DesignJobStatus::Assigned, $job->status);
        $this->assertSame(0, $job->revision_cycle_used);

        $history = $item->fresh()->statusHistories()->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame(ItemStatus::QueuedDesign, $history->from_status);
        $this->assertSame(ItemStatus::Designing, $history->to_status);
        $this->assertSame($designer->id, $history->changed_by);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'design.claimed',
            'user_id' => $designer->id,
            'model_type' => DesignJob::class,
            'model_id' => $job->id,
        ]);
    }

    #[Test]
    public function it_rejects_claim_when_item_is_not_queued_design(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item ACC', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);

        $item = $order->items->first();
        $item->update(['status' => ItemStatus::Acc]);

        $response = $this->actingAs($designer)->post(route('design.claim', $item));

        $response->assertRedirect();
        $response->assertSessionHasErrors('item');

        $this->assertSame(ItemStatus::Acc, $item->fresh()->status);
        $this->assertDatabaseCount('design_jobs', 0);
    }

    #[Test]
    public function it_rejects_second_claim_on_same_item(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Dup', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);

        $item = $order->items->first();

        app(DesignService::class)->claim($item, $designer);

        $second = User::factory()->forRole(UserRole::Designer)->create();
        $response = $this->actingAs($second)->post(route('design.claim', $item->fresh()));

        $response->assertRedirect();
        $response->assertSessionHasErrors('item');

        $this->assertDatabaseCount('design_jobs', 1);
    }

    #[Test]
    public function it_forbids_roles_without_designs_claim(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Forbidden', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);

        $item = $order->items->first();

        $production = User::factory()->forRole(UserRole::Production)->create();

        $this->actingAs($production)->post(route('design.claim', $item))
            ->assertForbidden();
    }

    #[Test]
    public function it_throws_validation_exception_when_service_claims_non_queued_item(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Service Guard', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);

        $item = $order->items->first();
        $item->update(['status' => ItemStatus::QueuedProduction]);

        $this->expectException(ValidationException::class);

        app(DesignService::class)->claim($item->fresh(), $designer);
    }
}
