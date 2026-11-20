<?php

namespace Tests\Feature\Design;

use App\Enums\CustomerType;
use App\Enums\DesignFileKind;
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

class DesignApprovalTest extends TestCase
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
    public function it_submits_for_approval_and_moves_item_to_awaiting_acc(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Submit', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        $response = $this->actingAs($designer)->post(route('design.submit', $item));

        $response->assertRedirect(route('design.upload', $item));

        $this->assertSame(ItemStatus::AwaitingAcc, $item->fresh()->status);

        $history = $item->fresh()->statusHistories()->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame(ItemStatus::Designing, $history->from_status);
        $this->assertSame(ItemStatus::AwaitingAcc, $history->to_status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'design.submitted',
            'user_id' => $designer->id,
        ]);
    }

    #[Test]
    public function it_approves_locks_final_file_and_creates_approval(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Approve', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('draft-v1.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('draft-v2.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($designer)->post(route('design.submit', $item))->assertRedirect();

        $response = $this->actingAs($admin)->post(route('design.approve', $item), [
            'method' => 'customer',
            'note' => 'OK',
        ]);

        $response->assertRedirect(route('design.upload', $item));

        $this->assertSame(ItemStatus::Acc, $item->fresh()->status);

        $job = DesignJob::where('order_item_id', $item->id)->firstOrFail();
        $files = DesignFile::where('design_job_id', $job->id)->orderBy('version')->get();
        $this->assertCount(2, $files);
        $this->assertSame(DesignFileKind::Draft, $files[0]->kind);
        $this->assertSame(DesignFileKind::Final, $files[1]->kind);

        $this->assertDatabaseHas('design_approvals', [
            'design_job_id' => $job->id,
            'design_file_id' => $files[1]->id,
            'approved_by' => $admin->id,
            'method' => 'customer',
            'note' => 'OK',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'design.approved',
            'user_id' => $admin->id,
        ]);
    }

    #[Test]
    public function it_rejects_approve_without_submit(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item No Submit', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        $response = $this->actingAs($admin)->post(route('design.approve', $item), [
            'method' => 'internal',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('item');

        $this->assertNotSame(ItemStatus::Acc, $item->fresh()->status);
    }

    #[Test]
    public function it_forbids_approve_for_designer_and_finance(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $finance = User::factory()->forRole(UserRole::Finance)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Forbidden Approve', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);
        $this->actingAs($designer)->post(route('design.submit', $item))->assertRedirect();

        $this->actingAs($designer)->post(route('design.approve', $item), ['method' => 'internal'])
            ->assertForbidden();

        $this->actingAs($finance)->post(route('design.approve', $item), ['method' => 'internal'])
            ->assertForbidden();
    }

    #[Test]
    public function it_rejects_upload_and_revision_when_item_is_acc(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item ACC Guard', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('v1.pdf', 5, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($designer)->post(route('design.submit', $item))->assertRedirect();
        $this->actingAs($admin)->post(route('design.approve', $item), ['method' => 'internal'])->assertRedirect();

        $this->assertSame(ItemStatus::Acc, $item->fresh()->status);

        $uploadResponse = $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('late.pdf', 5, 'application/pdf'),
        ]);
        $uploadResponse->assertRedirect();
        $uploadResponse->assertSessionHasErrors('item');

        $reviseResponse = $this->actingAs($admin)->post(route('design.revise', $item), [
            'note' => 'Catatan revisi yang cukup panjang',
        ]);
        $reviseResponse->assertRedirect();
        $reviseResponse->assertSessionHasErrors('item');
    }

    #[Test]
    public function it_keeps_v1_when_v2_becomes_final(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Versions', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('draft-v1.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('draft-v2.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($designer)->post(route('design.submit', $item))->assertRedirect();
        $this->actingAs($admin)->post(route('design.approve', $item), ['method' => 'internal'])->assertRedirect();

        $job = DesignJob::where('order_item_id', $item->id)->firstOrFail();
        $files = DesignFile::where('design_job_id', $job->id)->orderBy('version')->get();
        $this->assertCount(2, $files);
        $this->assertSame(1, $files[0]->version);
        $this->assertSame(2, $files[1]->version);
        $this->assertSame(DesignFileKind::Final, $files[1]->kind);
        Storage::disk('local')->assertExists($files[0]->path);
        Storage::disk('local')->assertExists($files[1]->path);
    }

    #[Test]
    public function it_does_not_allow_two_final_files_in_one_job(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Two Final', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('draft-v1.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('draft-v2.pdf', 10, 'application/pdf'),
        ])->assertRedirect();

        $this->actingAs($designer)->post(route('design.submit', $item))->assertRedirect();

        $job = DesignJob::where('order_item_id', $item->id)->firstOrFail();
        $v1 = DesignFile::where('design_job_id', $job->id)->where('version', 1)->firstOrFail();
        // tandai v1 sebagai final secara manual untuk mensimulasikan final lain
        $v1->update(['kind' => DesignFileKind::Final]);

        $v2 = DesignFile::where('design_job_id', $job->id)->where('version', 2)->firstOrFail();

        $this->expectException(ValidationException::class);

        app(DesignService::class)->approve($job->fresh(), $admin, 'internal', null, $v2->id);
    }
}
