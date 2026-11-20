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
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DesignUploadTest extends TestCase
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
    public function it_uploads_v1_then_v2_without_overwriting(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Upload', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        $this->actingAs($designer)
            ->post(route('design.upload', $item), [
                'file' => UploadedFile::fake()->create('draft-v1.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect(route('design.upload', $item));

        $first = DesignFile::where('design_job_id', DesignJob::where('order_item_id', $item->id)->first()->id)->orderBy('version')->first();
        $this->assertNotNull($first);
        $this->assertSame(1, $first->version);
        Storage::disk('local')->assertExists($first->path);

        $this->actingAs($designer)
            ->post(route('design.upload', $item), [
                'file' => UploadedFile::fake()->create('draft-v2.pdf', 10, 'application/pdf'),
            ])
            ->assertRedirect(route('design.upload', $item));

        $files = DesignFile::where('design_job_id', $first->design_job_id)->orderBy('version')->get();
        $this->assertCount(2, $files);
        $this->assertSame(1, $files[0]->version);
        $this->assertSame(2, $files[1]->version);
        $this->assertNotSame($files[0]->path, $files[1]->path);
        Storage::disk('local')->assertExists($files[0]->path);
        Storage::disk('local')->assertExists($files[1]->path);
    }

    #[Test]
    public function it_sequences_versions_1_2_3(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Seq', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($designer)
                ->post(route('design.upload', $item), [
                    'file' => UploadedFile::fake()->create("v{$i}.pdf", 5, 'application/pdf'),
                ])
                ->assertRedirect();
        }

        $job = DesignJob::where('order_item_id', $item->id)->first();
        $versions = DesignFile::where('design_job_id', $job->id)->orderBy('version')->pluck('version')->all();
        $this->assertSame([1, 2, 3], $versions);
    }

    #[Test]
    public function it_rejects_invalid_type_and_oversized_file(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Invalid', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        $this->actingAs($designer)
            ->postJson(route('design.upload', $item), [
                'file' => UploadedFile::fake()->create('bad.txt', 5, 'text/plain'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->actingAs($designer)
            ->postJson(route('design.upload', $item), [
                'file' => UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    #[Test]
    public function it_forbids_designer_without_ownership_and_roles_without_permission(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $other = User::factory()->forRole(UserRole::Designer)->create();
        $production = User::factory()->forRole(UserRole::Production)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Forbidden', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        $this->actingAs($other)
            ->post(route('design.upload', $item), [
                'file' => UploadedFile::fake()->create('x.pdf', 5, 'application/pdf'),
            ])
            ->assertForbidden();

        $this->actingAs($production)
            ->post(route('design.upload', $item), [
                'file' => UploadedFile::fake()->create('x.pdf', 5, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    #[Test]
    public function it_requires_policy_for_download_and_keeps_file_private(): void
    {
        Storage::fake('local');

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $field = User::factory()->forRole(UserRole::Field)->create();

        $order = $this->makeValidatedOrder($designer, [
            ['title' => 'Item Download', 'category' => ItemCategory::Printing->value, 'qty' => 1, 'unit' => 'pcs', 'unit_price' => 10000],
        ]);
        $item = $this->claimItem($designer, $order);

        $this->actingAs($designer)
            ->post(route('design.upload', $item), [
                'file' => UploadedFile::fake()->create('secret.pdf', 5, 'application/pdf'),
            ])
            ->assertRedirect();

        $file = DesignFile::firstOrFail();

        $this->actingAs($designer)
            ->get(route('design.file.download', $file))
            ->assertOk();

        $this->actingAs($field)
            ->get(route('design.file.download', $file))
            ->assertForbidden();

        Storage::disk('local')->assertExists($file->path);
        Storage::disk('public')->assertMissing($file->path);
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
