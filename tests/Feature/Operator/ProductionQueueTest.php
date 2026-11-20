<?php

namespace Tests\Feature\Operator;

use App\Enums\CustomerType;
use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\PrintQueueClass;
use App\Enums\Priority;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\OrderItem;
use App\Models\PrintJob;
use App\Models\User;
use App\Models\WorkCalendar;
use App\Services\Customer\CustomerService;
use App\Services\Design\DesignService;
use App\Services\Order\OrderService;
use App\Services\Production\ProductionService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductionQueueTest extends TestCase
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

    private function makeAccItem(User $designer, User $approver, array $itemOverrides = []): OrderItem
    {
        Storage::fake('local');

        $customer = $this->activeCustomer($designer);
        $itemData = array_merge([
            'title' => 'Item Produksi',
            'category' => ItemCategory::Printing->value,
            'qty' => 10,
            'unit' => 'pcs',
            'unit_price' => 10000,
        ], $itemOverrides);

        $order = app(OrderService::class)->create([
            'customer_id' => $customer->id,
            'items' => [$itemData],
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

    private function handoffToProduction(OrderItem $item, User $admin): PrintJob
    {
        app(DesignService::class)->handoffToProduction($item, $admin);

        return PrintJob::query()->where('order_item_id', $item->id)->firstOrFail();
    }

    #[Test]
    public function it_classifies_item_with_monday_deadline_as_h1_on_friday(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        // Deadline Senin 2026-11-23; Jumat sebelumnya 2026-11-20.
        $deadline = Carbon::parse('2026-11-23 10:00:00', config('app.timezone'));
        $item = $this->makeAccItem($designer, $admin, [
            'deadline_at' => $deadline,
            'priority' => Priority::Normal->value,
        ]);

        $job = $this->handoffToProduction($item, $admin);

        $asFriday = Carbon::parse('2026-11-20 08:00:00', config('app.timezone'));
        $queueClass = app(ProductionService::class)->computeQueueClass($item->fresh(), $asFriday);

        $this->assertSame(PrintQueueClass::H1, $queueClass);

        // Tetap regular jika diperiksa jauh sebelum H-1.
        $asThursday = Carbon::parse('2026-11-19 08:00:00', config('app.timezone'));
        $this->assertSame(
            PrintQueueClass::Regular,
            app(ProductionService::class)->computeQueueClass($item->fresh(), $asThursday),
        );
    }

    #[Test]
    public function it_skips_holiday_when_calculating_h1_deadline(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        // Deadline Rabu 2026-11-25. Selasa 2026-11-24 libur -> H-1 menjadi Senin 2026-11-23.
        WorkCalendar::query()->create([
            'date' => '2026-11-24',
            'is_workday' => false,
            'note' => 'Libur uji',
        ]);

        $deadline = Carbon::parse('2026-11-25 10:00:00', config('app.timezone'));
        $item = $this->makeAccItem($designer, $admin, [
            'deadline_at' => $deadline,
            'priority' => Priority::Normal->value,
        ]);

        $this->handoffToProduction($item, $admin);

        $asMonday = Carbon::parse('2026-11-23 08:00:00', config('app.timezone'));
        $asFridayBefore = Carbon::parse('2026-11-20 08:00:00', config('app.timezone'));

        $service = app(ProductionService::class);
        $this->assertSame(PrintQueueClass::H1, $service->computeQueueClass($item->fresh(), $asMonday));
        $this->assertSame(PrintQueueClass::Regular, $service->computeQueueClass($item->fresh(), $asFridayBefore));
    }

    #[Test]
    public function it_separates_urgent_h1_and_regular_queues_on_queue_page(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $production = User::factory()->forRole(UserRole::Production)->create();

        Carbon::setTestNow(Carbon::parse('2026-11-20 08:00:00', config('app.timezone')));

        $urgentItem = $this->makeAccItem($designer, $admin, [
            'title' => 'Item Urgent Queue',
            'priority' => Priority::Urgent->value,
            'deadline_at' => '2026-11-30 10:00:00',
        ]);
        $h1Item = $this->makeAccItem($designer, $admin, [
            'title' => 'Item H1 Queue',
            'priority' => Priority::Normal->value,
            'deadline_at' => '2026-11-23 10:00:00', // Senin, sekarang Jumat
        ]);
        $regularItem = $this->makeAccItem($designer, $admin, [
            'title' => 'Item Reguler Queue',
            'priority' => Priority::Normal->value,
            'deadline_at' => '2026-12-10 10:00:00',
        ]);

        $this->handoffToProduction($urgentItem, $admin);
        $this->handoffToProduction($h1Item, $admin);
        $this->handoffToProduction($regularItem, $admin);

        $response = $this->actingAs($production)->get(route('production.index'));

        $response->assertOk();
        $response->assertSee('Item Urgent Queue');
        $response->assertSee('Item H1 Queue');
        $response->assertSee('Item Reguler Queue');
        $response->assertSee('Urgent');
        $response->assertSee('H-1');
        $response->assertSee('Reguler');

        $this->assertSame(PrintQueueClass::Urgent, PrintJob::where('order_item_id', $urgentItem->id)->firstOrFail()->queue_class);
        $this->assertSame(PrintQueueClass::H1, PrintJob::where('order_item_id', $h1Item->id)->firstOrFail()->queue_class);
        $this->assertSame(PrintQueueClass::Regular, PrintJob::where('order_item_id', $regularItem->id)->firstOrFail()->queue_class);

        Carbon::setTestNow();
    }

    #[Test]
    public function it_creates_print_job_on_handoff_to_production(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $item = $this->makeAccItem($designer, $admin, [
            'title' => 'Item Intake',
            'qty' => 25,
            'deadline_at' => '2026-12-01 10:00:00',
            'priority' => Priority::Normal->value,
        ]);

        $this->assertDatabaseCount('print_jobs', 0);

        $this->actingAs($admin)->post(route('design.handoff', $item))->assertRedirect();

        $job = PrintJob::query()->where('order_item_id', $item->id)->first();
        $this->assertNotNull($job);
        $this->assertSame(ItemStatus::QueuedProduction, $item->fresh()->status);
        $this->assertSame('25.00', (string) $job->qty_planned);
    }
}
