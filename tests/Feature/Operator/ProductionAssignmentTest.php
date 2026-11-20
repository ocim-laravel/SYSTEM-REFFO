<?php

namespace Tests\Feature\Operator;

use App\Enums\CustomerType;
use App\Enums\ItemCategory;
use App\Enums\Priority;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\PrintJob;
use App\Models\User;
use App\Services\Customer\CustomerService;
use App\Services\Design\DesignService;
use App\Services\Order\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductionAssignmentTest extends TestCase
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
            'name' => 'PT Pelanggan Produksi',
            'type' => CustomerType::Company->value,
            'is_active' => true,
        ], $actor);
    }

    private function makeAccItem(User $designer, User $approver): OrderItem
    {
        Storage::fake('local');

        $customer = $this->activeCustomer($designer);

        $order = app(OrderService::class)->create([
            'customer_id' => $customer->id,
            'items' => [[
                'title' => 'Item Assign',
                'category' => ItemCategory::Printing->value,
                'qty' => 10,
                'unit' => 'pcs',
                'unit_price' => 10000,
                'priority' => Priority::Normal->value,
                'deadline_at' => '2026-12-10 10:00:00',
            ]],
        ], $designer);

        app(OrderService::class)->validate($order, $approver);

        $item = $order->fresh()->items->first();

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

    private function makeProductionJob(User $designer, User $admin): array
    {
        $item = $this->makeAccItem($designer, $admin);
        app(DesignService::class)->handoffToProduction($item, $admin);
        $job = PrintJob::query()->where('order_item_id', $item->id)->firstOrFail();

        return [$item->fresh(), $job];
    }

    #[Test]
    public function it_forbids_regular_operator_from_assigning_work_to_another_operator(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operatorA = User::factory()->forRole(UserRole::Production)->create();
        $operatorB = User::factory()->forRole(UserRole::Production)->create();

        [$item] = $this->makeProductionJob($designer, $admin);

        $this->actingAs($operatorA)
            ->post(route('production.assign', $item), ['operator_id' => $operatorB->id])
            ->assertForbidden();

        $job = PrintJob::query()->where('order_item_id', $item->id)->firstOrFail();
        $this->assertNull($job->assigned_to);
        $this->assertDatabaseCount('print_job_operators', 0);
    }

    #[Test]
    public function it_allows_admin_to_assign_work_to_operator(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operator = User::factory()->forRole(UserRole::Production)->create();

        [$item] = $this->makeProductionJob($designer, $admin);

        $this->actingAs($admin)
            ->post(route('production.assign', $item), ['operator_id' => $operator->id])
            ->assertRedirect(route('production.show', $item));

        $job = PrintJob::query()->where('order_item_id', $item->id)->firstOrFail();
        $this->assertSame($operator->id, $job->assigned_to);
        $this->assertSame($admin->id, $job->assigned_by);

        $this->assertDatabaseHas('print_job_operators', [
            'print_job_id' => $job->id,
            'operator_id' => $operator->id,
            'ended_at' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'production.assigned',
            'user_id' => $admin->id,
            'model_type' => PrintJob::class,
            'model_id' => $job->id,
        ]);
    }

    #[Test]
    public function it_allows_authorized_operator_to_claim_and_prevents_claim_when_already_held(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operator = User::factory()->forRole(UserRole::Production)->create();
        $otherOperator = User::factory()->forRole(UserRole::Production)->create();

        [$item] = $this->makeProductionJob($designer, $admin);

        $this->actingAs($operator)
            ->post(route('production.claim', $item))
            ->assertRedirect(route('production.show', $item));

        $job = PrintJob::query()->where('order_item_id', $item->id)->firstOrFail();
        $this->assertSame($operator->id, $job->assigned_to);

        $this->assertDatabaseHas('print_job_operators', [
            'print_job_id' => $job->id,
            'operator_id' => $operator->id,
            'ended_at' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'production.claimed',
            'user_id' => $operator->id,
        ]);

        // Operator lain tidak bisa claim item yang sudah dipegang.
        $this->actingAs($otherOperator)
            ->post(route('production.claim', $item))
            ->assertRedirect();

        $this->assertSame(1, $job->operators()->whereNull('ended_at')->count());
        $this->assertSame($operator->id, $job->fresh()->assigned_to);
    }
}
