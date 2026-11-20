<?php

namespace Tests\Feature\Operator;

use App\Enums\CustomerType;
use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\PrintJobStatus;
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

class ProductionProgressHandoverTest extends TestCase
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

    private function makeProductionItem(User $designer, User $admin): OrderItem
    {
        Storage::fake('local');

        $customer = $this->activeCustomer($designer);

        $order = app(OrderService::class)->create([
            'customer_id' => $customer->id,
            'items' => [[
                'title' => 'Item Progres',
                'category' => ItemCategory::Printing->value,
                'qty' => 10,
                'unit' => 'pcs',
                'unit_price' => 10000,
                'priority' => Priority::Normal->value,
                'deadline_at' => '2026-12-10 10:00:00',
            ]],
        ], $designer);

        app(OrderService::class)->validate($order, $admin);
        $item = $order->fresh()->items->first();

        app(DesignService::class)->claim($item, $designer);
        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('final-v1.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $this->actingAs($designer)->post(route('design.submit', $item))->assertRedirect();
        $this->actingAs($admin)->post(route('design.approve', $item), ['method' => 'internal'])->assertRedirect();

        app(DesignService::class)->handoffToProduction($item->fresh(), $admin);

        return $item->fresh();
    }

    #[Test]
    public function it_closes_old_operator_record_and_opens_new_one_on_handover(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operatorA = User::factory()->forRole(UserRole::Production)->create();
        $operatorB = User::factory()->forRole(UserRole::Production)->create();

        $item = $this->makeProductionItem($designer, $admin);
        $job = PrintJob::query()->where('order_item_id', $item->id)->firstOrFail();

        $this->actingAs($operatorA)->post(route('production.claim', $item))->assertRedirect();

        $old = $job->operators()->where('operator_id', $operatorA->id)->whereNull('ended_at')->firstOrFail();

        $this->actingAs($operatorA)
            ->post(route('production.handover', $item), [
                'operator_id' => $operatorB->id,
                'reason' => 'Pergantian shift sore',
            ])
            ->assertRedirect(route('production.show', $item));

        $old->refresh();
        $this->assertNotNull($old->ended_at);
        $this->assertSame('Pergantian shift sore', $old->reason);

        $new = $job->operators()->where('operator_id', $operatorB->id)->whereNull('ended_at')->first();
        $this->assertNotNull($new);
        $this->assertSame($operatorB->id, $job->fresh()->assigned_to);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'production.handover',
            'user_id' => $operatorA->id,
            'model_type' => PrintJob::class,
            'model_id' => $job->id,
        ]);
    }

    #[Test]
    public function it_moves_item_to_printed_after_actual_qty_is_recorded(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operator = User::factory()->forRole(UserRole::Production)->create();

        $item = $this->makeProductionItem($designer, $admin);
        $job = PrintJob::query()->where('order_item_id', $item->id)->firstOrFail();

        $this->actingAs($operator)->post(route('production.claim', $item))->assertRedirect();

        $this->actingAs($operator)
            ->post(route('production.progress', $item), [
                'qty_planned' => '10.00',
                'qty_actual' => '10.00',
                'mark_printed' => '1',
            ])
            ->assertRedirect(route('production.show', $item));

        $job->refresh();
        $item->refresh();

        $this->assertSame(PrintJobStatus::Completed, $job->status);
        $this->assertSame('10.00', (string) $job->qty_actual);
        $this->assertSame(ItemStatus::Printed, $item->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'production.progress_recorded',
            'user_id' => $operator->id,
            'model_type' => PrintJob::class,
            'model_id' => $job->id,
        ]);
    }
}
