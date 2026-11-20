<?php

namespace Tests\Feature\Design;

use App\Enums\CustomerType;
use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\DesignFile;
use App\Models\DesignJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Customer\CustomerService;
use App\Services\Design\DesignService;
use App\Services\Order\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DesignRevisionCycleTest extends TestCase
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

    private function claimItem(User $designer, Order $order): OrderItem
    {
        $item = $order->items->first();
        app(DesignService::class)->claim($item, $designer);

        return $item->fresh();
    }

    #[Test]
    public function it_allows_three_revision_cycles_with_correct_cycle_no(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Revisi', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);
        $job = DesignJob::where('order_item_id', $item->id)->firstOrFail();

        for ($i = 1; $i <= 3; $i++) {
            $revision = app(DesignService::class)->requestRevision($job, "Catatan revisi siklus ke-{$i}", $admin);
            $this->assertSame($i, $revision->cycle_no);
            $job = $job->fresh();
        }

        $this->assertSame(3, $job->revision_cycle_used);
        $this->assertSame(ItemStatus::Revision, $item->fresh()->status);
        $this->assertDatabaseCount('design_revisions', 3);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'design.revision.requested',
            'user_id' => $admin->id,
        ]);
        $this->assertGreaterThan(0, $item->fresh()->statusHistories()->count());
    }

    #[Test]
    public function it_rejects_fourth_revision_and_offers_change_request(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Revisi 4', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);
        $job = DesignJob::where('order_item_id', $item->id)->firstOrFail();

        for ($i = 1; $i <= 3; $i++) {
            app(DesignService::class)->requestRevision($job, "Catatan revisi siklus ke-{$i}", $admin);
            $job = $job->fresh();
        }

        try {
            app(DesignService::class)->requestRevision($job->fresh(), 'Revisi keempat', $admin);
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $e) {
            $this->assertSame('Siklus revisi gratis (3) habis. Ajukan Change Request.', $e->errors()['revision'][0]);
            $this->assertTrue($e->errors()['change_request_offered'][0]);
        }
    }

    #[Test]
    public function it_rejects_revision_without_note_min_five_chars(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Revisi Note', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);
        $job = DesignJob::where('order_item_id', $item->id)->firstOrFail();

        foreach (['', 'ab', 'abcd'] as $short) {
            try {
                app(DesignService::class)->requestRevision($job->fresh(), $short, $admin);
                $this->fail('Expected ValidationException was not thrown for note: '.$short);
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('note', $e->errors());
            }
        }
    }

    #[Test]
    public function it_forbids_roles_without_designs_revise(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Forbidden Revise', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        $this->actingAs($designer)
            ->post(route('design.revise', $item), ['note' => 'Catatan revisi yang cukup panjang'])
            ->assertForbidden();
    }

    #[Test]
    public function it_allows_designer_to_upload_v2_after_revision(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Upload Revisi', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        $this->actingAs($designer)
            ->post(route('design.upload', $item), [
                'file' => UploadedFile::fake()->create('draft-v1.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect(route('design.upload', $item));

        $job = DesignJob::where('order_item_id', $item->id)->firstOrFail();
        app(DesignService::class)->requestRevision($job, 'Perlu revisi ukuran', $admin);

        $this->actingAs($designer)
            ->post(route('design.upload', $item), [
                'file' => UploadedFile::fake()->create('draft-v2.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect(route('design.upload', $item));

        $files = DesignFile::where('design_job_id', $job->id)->orderBy('version')->get();
        $this->assertCount(2, $files);
        $this->assertSame(1, $files[0]->version);
        $this->assertSame(2, $files[1]->version);
    }

    #[Test]
    public function it_rejects_upload_when_item_is_acc(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item ACC', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);
        $item->update(['status' => ItemStatus::Acc]);

        $response = $this->actingAs($designer)
            ->post(route('design.upload', $item), [
                'file' => UploadedFile::fake()->create('late.pdf', 5, 'application/pdf'),
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('item');

        $job = DesignJob::where('order_item_id', $item->id)->first();
        $this->assertDatabaseCount('design_files', 0);
        Storage::disk('local')->assertMissing('design/'.$job->id.'/v1.pdf');
    }
}
