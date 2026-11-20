<?php

namespace Tests\Feature\Design;

use App\Enums\ChangeRequestStatus;
use App\Enums\CustomerType;
use App\Enums\DesignFileKind;
use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\UserRole;
use App\Models\ChangeRequest;
use App\Models\Customer;
use App\Models\DesignApproval;
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

class DesignChangeRequestTest extends TestCase
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
            'name' => 'PT Pelanggan CR',
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

    private function makeAccItem(User $designer, User $approver): array
    {
        Storage::fake('local');

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item ACC CR', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('draft-v1.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $this->actingAs($designer)->post(route('design.submit', $item))->assertRedirect();
        $this->actingAs($approver)->post(route('design.approve', $item), [
            'method' => 'internal',
        ])->assertRedirect();

        $job = DesignJob::where('order_item_id', $item->id)->firstOrFail();

        return [$item->fresh(), $job];
    }

    #[Test]
    public function it_stores_change_request_with_required_fields_for_acc_item(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        [$item, $job] = $this->makeAccItem($designer, $admin);
        $file = $job->files()->latest('version')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('design.change_request', $item), [
            'reason' => 'Pelanggan minta ubah warna utama',
            'cost_impact' => '150000.50',
            'deadline_impact' => '2026-12-31 10:00:00',
            'design_file_id' => $file->id,
        ]);

        $response->assertRedirect(route('design.upload', $item));
        $response->assertSessionHas('status');

        $cr = ChangeRequest::query()->firstOrFail();

        $this->assertSame($item->id, $cr->order_item_id);
        $this->assertSame($file->id, $cr->design_file_id);
        $this->assertSame('Pelanggan minta ubah warna utama', $cr->reason);
        $this->assertSame('150000.50', (string) $cr->cost_impact);
        $this->assertSame('2026-12-31 10:00:00', $cr->deadline_impact?->format('Y-m-d H:i:s'));
        $this->assertSame(ChangeRequestStatus::Pending, $cr->status);
        $this->assertNull($cr->approved_by);
        $this->assertNull($cr->approved_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'design.change_request.raised',
            'user_id' => $admin->id,
            'model_type' => ChangeRequest::class,
            'model_id' => $cr->id,
        ]);
    }

    #[Test]
    public function it_forbids_designer_from_creating_change_request(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        [$item] = $this->makeAccItem($designer, $admin);

        $this->actingAs($designer)->post(route('design.change_request', $item), [
            'reason' => 'Coba ajukan CR',
            'cost_impact' => '1000',
        ])->assertForbidden();

        $this->assertDatabaseCount('change_requests', 0);
    }

    #[Test]
    public function it_rejects_change_request_when_item_is_not_acc(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Belum ACC', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        $response = $this->actingAs($admin)->post(route('design.change_request', $item), [
            'reason' => 'Perubahan saat belum ACC',
            'cost_impact' => '5000',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('item');
        $this->assertDatabaseCount('change_requests', 0);
    }

    #[Test]
    public function it_approves_change_request_sets_approver_and_reopens_item(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $owner = User::factory()->forRole(UserRole::Owner)->create();

        [$item, $job] = $this->makeAccItem($designer, $admin);

        $cr = app(DesignService::class)->requestChangeRequest(
            $item,
            $admin,
            'Perlu ubah layout dan ukuran',
            '25000',
            '2026-12-20 08:00:00',
        );

        $response = $this->actingAs($owner)->post(route('design.change_request.approve', $cr));

        $response->assertRedirect(route('design.upload', $item));

        $cr = $cr->fresh();
        $item = $item->fresh();

        $this->assertSame(ChangeRequestStatus::Approved, $cr->status);
        $this->assertSame($owner->id, $cr->approved_by);
        $this->assertNotNull($cr->approved_at);
        $this->assertNotNull($cr->applied_at);

        $this->assertSame(ItemStatus::Revision, $item->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'design.change_request.approved',
            'user_id' => $owner->id,
            'model_type' => ChangeRequest::class,
            'model_id' => $cr->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.change_request.applied',
            'user_id' => $owner->id,
            'model_type' => OrderItem::class,
            'model_id' => $item->id,
        ]);

        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('draft-v2.pdf', 10, 'application/pdf'),
        ])->assertRedirect(route('design.upload', $item));

        $this->assertSame(2, $job->fresh()->files()->count());
    }

    #[Test]
    public function it_rejects_change_request_without_changing_acc_item(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        [$item] = $this->makeAccItem($designer, $admin);

        $cr = app(DesignService::class)->requestChangeRequest(
            $item,
            $admin,
            'Permintaan ubah besar',
            '10000',
            null,
        );

        $response = $this->actingAs($admin)->post(route('design.change_request.reject', $cr));

        $response->assertRedirect(route('design.upload', $item));

        $cr = $cr->fresh();
        $this->assertSame(ChangeRequestStatus::Rejected, $cr->status);
        $this->assertSame($admin->id, $cr->approved_by);
        $this->assertNotNull($cr->approved_at);
        $this->assertNull($cr->applied_at);
        $this->assertSame(ItemStatus::Acc, $item->fresh()->status);
    }

    #[Test]
    public function it_does_not_allow_item_acc_to_be_changed_without_approved_change_request(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        [$item, $job] = $this->makeAccItem($designer, $admin);

        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('late.pdf', 5, 'application/pdf'),
        ])->assertSessionHasErrors('item');

        $this->actingAs($admin)->post(route('design.revise', $item), [
            'note' => 'Coba revisi saat ACC',
        ])->assertSessionHasErrors('item');

        $this->expectException(ValidationException::class);

        app(OrderService::class)->updateItemPrice($item->fresh(), 99999, $admin);
    }

    #[Test]
    public function it_allows_revision_after_approved_change_request_is_applied(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        [$item] = $this->makeAccItem($designer, $admin);

        $cr = app(DesignService::class)->requestChangeRequest(
            $item,
            $admin,
            'Butuh revisi lanjutan',
            '0',
            null,
        );

        app(DesignService::class)->approveChangeRequest($cr, $admin);

        $this->assertSame(ItemStatus::Revision, $item->fresh()->status);

        $revision = app(DesignService::class)->requestRevision(
            DesignJob::where('order_item_id', $item->id)->firstOrFail(),
            'Catatan revisi setelah CR disetujui',
            $admin,
        );

        $this->assertNotNull($revision->id);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'design.revision.requested',
            'user_id' => $admin->id,
        ]);
    }

    #[Test]
    public function it_reapproves_after_approved_change_request_and_hands_off_to_production(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $owner = User::factory()->forRole(UserRole::Owner)->create();

        [$item, $job] = $this->makeAccItem($designer, $admin);
        $firstFile = $job->files()->latest('version')->firstOrFail();

        $cr = app(DesignService::class)->requestChangeRequest(
            $item,
            $admin,
            'ACC ulang: ubah warna utama',
            '75000',
            '2026-12-30 09:00:00',
        );
        app(DesignService::class)->approveChangeRequest($cr, $owner);

        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('draft-v2.pdf', 10, 'application/pdf'),
        ])->assertRedirect(route('design.upload', $item));
        $this->actingAs($designer)->post(route('design.submit', $item))->assertRedirect();
        $this->actingAs($admin)->post(route('design.approve', $item), [
            'method' => 'internal',
            'note' => 'ACC ulang setelah CR disetujui',
        ])->assertRedirect(route('design.upload', $item));

        $item = $item->fresh();
        $secondFile = $job->fresh()->files()->latest('version')->firstOrFail();

        $this->assertSame(ItemStatus::Acc, $item->status);
        $this->assertSame(DesignFileKind::Final, $secondFile->kind);
        $this->assertSame(DesignFileKind::Draft, $firstFile->fresh()->kind);
        $this->assertSame(2, $job->fresh()->files()->count());
        $this->assertSame(1, $job->fresh()->files()->where('kind', DesignFileKind::Final->value)->count());
        $this->assertSame(2, DesignApproval::query()->where('design_job_id', $job->id)->count());
        $this->assertDatabaseHas('design_approvals', [
            'design_job_id' => $job->id,
            'design_file_id' => $firstFile->id,
        ]);
        $this->assertDatabaseHas('design_approvals', [
            'design_job_id' => $job->id,
            'design_file_id' => $secondFile->id,
        ]);

        $history = app(DesignService::class)->history($job->fresh());
        $this->assertCount(2, $history->where('type', 'file'));
        $this->assertCount(2, $history->where('type', 'approval'));

        app(DesignService::class)->handoffToProduction($item, $admin);
        $this->assertSame(ItemStatus::QueuedProduction, $item->fresh()->status);
    }

    #[Test]
    public function it_requires_a_new_change_request_for_each_reapproval(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        [$item, $job] = $this->makeAccItem($designer, $admin);

        $cr = app(DesignService::class)->requestChangeRequest($item, $admin, 'Perubahan pertama', '10000', null);
        app(DesignService::class)->approveChangeRequest($cr, $admin);

        $this->actingAs($designer)->post(route('design.upload', $item), [
            'file' => UploadedFile::fake()->create('draft-v2.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $this->actingAs($designer)->post(route('design.submit', $item))->assertRedirect();
        $this->actingAs($admin)->post(route('design.approve', $item))->assertRedirect();

        // CR yang sama tidak boleh dipakai untuk ACC ulang kedua.
        $item->update(['status' => ItemStatus::AwaitingAcc]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('File ini sudah menjadi final; ACC ulang wajib memilih versi baru.');

        app(DesignService::class)->approve($job->fresh(), $admin);
    }
}
