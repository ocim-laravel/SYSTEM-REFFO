<?php

namespace Tests\Feature\Design;

use App\Enums\CustomerType;
use App\Enums\ItemCategory;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DesignApproval;
use App\Models\DesignFile;
use App\Models\DesignJob;
use App\Models\DesignRevision;
use App\Models\Order;
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

class DesignHistoryTest extends TestCase
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
            'name' => 'PT Pelanggan Riwayat Desain',
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
    public function it_shows_versions_revisions_and_approval_in_order_with_their_actors(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Riwayat', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $order->items->first();
        app(DesignService::class)->claim($item, $designer);
        $job = DesignJob::where('order_item_id', $item->id)->firstOrFail();

        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('v1.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $v1 = DesignFile::query()->where('version', 1)->firstOrFail();
        DesignFile::query()->whereKey($v1->id)->update(['created_at' => '2026-11-20 08:00:00']);

        app(DesignService::class)->requestRevision($job, 'Ubah ukuran dan warna', $admin);
        $revision = DesignRevision::query()->firstOrFail();
        DesignRevision::query()->whereKey($revision->id)->update(['requested_at' => '2026-11-20 08:10:00']);

        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('v2.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $v2 = DesignFile::query()->where('version', 2)->firstOrFail();
        DesignFile::query()->whereKey($v2->id)->update(['created_at' => '2026-11-20 08:20:00']);

        $this->actingAs($designer)->post(route('design.submit', $item))->assertRedirect();
        $this->actingAs($admin)->post(route('design.approve', $item), [
            'method' => 'internal',
        ])->assertRedirect();
        $approval = DesignApproval::query()->firstOrFail();
        DesignApproval::query()->whereKey($approval->id)->update(['approved_at' => '2026-11-20 08:40:00']);

        $response = $this->actingAs($designer)->get(route('design.upload', $item));

        $response->assertOk();
        $response->assertSee('Riwayat Lengkap Desain');
        $response->assertSee('Ubah ukuran dan warna');
        $response->assertSee($designer->name);
        $response->assertSee($admin->name);

        $content = $response->getContent();
        $version1 = strpos($content, 'Versi v1');
        $revision1 = strpos($content, 'Revisi siklus ke-1');
        $version2 = strpos($content, 'Versi v2');
        $acc = strpos($content, 'ACC v2');

        $this->assertNotFalse($version1);
        $this->assertNotFalse($revision1);
        $this->assertNotFalse($version2);
        $this->assertNotFalse($acc);
        $this->assertLessThan($revision1, $version1);
        $this->assertLessThan($version2, $revision1);
        $this->assertLessThan($acc, $version2);
    }

    #[Test]
    public function it_shows_empty_history_state_before_any_design_activity(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Riwayat Kosong', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $order->items->first();
        app(DesignService::class)->claim($item, $designer);

        $this->actingAs($designer)->get(route('design.upload', $item))
            ->assertOk()
            ->assertSee('Belum ada riwayat desain.');
    }
}
