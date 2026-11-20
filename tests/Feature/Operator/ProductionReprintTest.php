<?php

namespace Tests\Feature\Operator;

use App\Enums\CustomerType;
use App\Enums\ItemCategory;
use App\Enums\Priority;
use App\Enums\ReprintStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\PrintJob;
use App\Models\ReprintRequest;
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

class ProductionReprintTest extends TestCase
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
                'title' => 'Item Reprint',
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
    public function it_does_not_change_actual_qty_when_reprint_is_requested_without_approval(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operator = User::factory()->forRole(UserRole::Production)->create();

        $item = $this->makeProductionItem($designer, $admin);
        $job = PrintJob::query()->where('order_item_id', $item->id)->firstOrFail();

        $this->actingAs($operator)->post(route('production.claim', $item))->assertRedirect();
        $this->actingAs($operator)->post(route('production.progress', $item), [
            'qty_actual' => '10.00',
            'mark_printed' => '1',
        ])->assertRedirect();

        $job->refresh();
        $this->assertSame('10.00', (string) $job->qty_actual);

        $this->actingAs($operator)
            ->post(route('production.reprint.request', $item), [
                'qty' => '2.00',
                'reason' => 'Hasil cetak buram pada sebagian lembar',
            ])
            ->assertRedirect(route('production.show', $item));

        $job->refresh();
        $this->assertSame('10.00', (string) $job->qty_actual, 'Qty aktual tidak boleh berubah tanpa approval.');

        $reprint = ReprintRequest::query()->where('print_job_id', $job->id)->first();
        $this->assertNotNull($reprint);
        $this->assertSame(ReprintStatus::Pending, $reprint->status);
        $this->assertNull($reprint->approved_by);
        $this->assertNull($reprint->actual_qty);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'production.reprint_requested',
            'user_id' => $operator->id,
            'model_type' => ReprintRequest::class,
            'model_id' => $reprint->id,
        ]);
    }

    #[Test]
    public function it_allows_authorized_supervisor_to_approve_reprint_and_record_actual_qty(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $operator = User::factory()->forRole(UserRole::Production)->create();

        $item = $this->makeProductionItem($designer, $admin);
        $job = PrintJob::query()->where('order_item_id', $item->id)->firstOrFail();

        $this->actingAs($operator)->post(route('production.claim', $item))->assertRedirect();
        $this->actingAs($operator)->post(route('production.progress', $item), [
            'qty_actual' => '10.00',
            'mark_printed' => '1',
        ])->assertRedirect();

        $this->actingAs($operator)->post(route('production.reprint.request', $item), [
            'qty' => '2.00',
            'reason' => 'Warna tidak sesuai proof',
        ])->assertRedirect();

        $reprint = ReprintRequest::query()->where('print_job_id', $job->id)->firstOrFail();

        // Operator biasa tidak boleh approve.
        $this->actingAs($operator)
            ->post(route('production.reprints.approve', $reprint))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('production.reprints.approve', $reprint), ['actual_qty' => '2.00'])
            ->assertRedirect(route('production.reprints.index'));

        $reprint->refresh();
        $job->refresh();

        $this->assertSame(ReprintStatus::Approved, $reprint->status);
        $this->assertSame($admin->id, $reprint->approved_by);
        $this->assertNotNull($reprint->approved_at);
        $this->assertSame('12.00', (string) $job->qty_actual, 'Qty aktual hanya berubah saat approval dengan actual_qty.');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'production.reprint_approved',
            'user_id' => $admin->id,
            'model_type' => ReprintRequest::class,
            'model_id' => $reprint->id,
        ]);
    }
}
