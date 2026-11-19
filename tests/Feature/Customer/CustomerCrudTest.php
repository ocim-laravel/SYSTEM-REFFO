<?php

namespace Tests\Feature\Customer;

use App\Enums\CustomerType;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\User;
use App\Services\Customer\CustomerService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    #[Test]
    public function it_creates_a_customer_with_an_automatic_cus_code_when_admin_submits_valid_data(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $response = $this->actingAs($admin)->post(route('customers.store'), [
            'code' => 'CUS-DITOLAK',
            'name' => 'PT Reffo Contoh',
            'type' => CustomerType::Company->value,
            'phone' => '081234567890',
            'whatsapp' => '081234567890',
            'email' => 'admin@reffo.test',
            'address' => 'Jl. Contoh No. 1',
            'notes' => 'Pelanggan uji',
        ]);

        $customer = Customer::query()->firstOrFail();
        $year = now()->year;

        $response->assertRedirect(route('customers.show', $customer));
        $this->assertSame(sprintf('CUS-%d-0001', $year), $customer->code);
        $this->assertSame('PT Reffo Contoh', $customer->name);
        $this->assertSame(CustomerType::Company, $customer->type);
        $this->assertSame($admin->id, $customer->created_by);
        $this->assertTrue($customer->is_active);
        $this->assertDatabaseMissing('customers', ['code' => 'CUS-DITOLAK']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer.created',
            'user_id' => $admin->id,
            'model_type' => Customer::class,
            'model_id' => $customer->id,
        ]);
    }

    #[Test]
    public function it_generates_unique_and_sequential_cus_codes_when_three_customers_are_created(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $service = app(CustomerService::class);
        $year = now()->year;

        $codes = collect(range(1, 3))->map(fn (int $seq): string => $service->create([
            'name' => 'Pelanggan '.$seq,
            'type' => CustomerType::Individual->value,
        ], $admin)->code)->all();

        $this->assertSame([
            sprintf('CUS-%d-0001', $year),
            sprintf('CUS-%d-0002', $year),
            sprintf('CUS-%d-0003', $year),
        ], $codes);
        $this->assertCount(3, array_unique($codes));
        $this->assertSame(3, Customer::query()->count());
    }

    #[Test]
    public function it_rejects_a_customer_without_a_name_when_admin_submits_invalid_data(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $response = $this->actingAs($admin)->post(route('customers.store'), [
            'name' => '',
            'type' => CustomerType::Individual->value,
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(0, Customer::query()->count());
    }

    #[Test]
    public function it_forbids_customer_creation_when_actor_is_finance(): void
    {
        $finance = User::factory()->forRole(UserRole::Finance)->create();

        $response = $this->actingAs($finance)->post(route('customers.store'), [
            'name' => 'PT Reffo Contoh',
            'type' => CustomerType::Company->value,
        ]);

        $response->assertForbidden();
        $this->assertSame(0, Customer::query()->count());
    }

    #[Test]
    public function it_forbids_customer_update_when_actor_is_finance(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $finance = User::factory()->forRole(UserRole::Finance)->create();
        $customer = app(CustomerService::class)->create([
            'name' => 'Nama Tetap',
            'type' => CustomerType::Individual->value,
        ], $admin);

        $response = $this->actingAs($finance)->put(route('customers.update', $customer), [
            'name' => 'Nama Diubah',
            'type' => CustomerType::Company->value,
        ]);

        $response->assertForbidden();
        $customer->refresh();
        $this->assertSame('Nama Tetap', $customer->name);
        $this->assertSame(CustomerType::Individual, $customer->type);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'customer.updated']);
    }

    #[Test]
    public function it_forbids_the_edit_form_when_actor_is_finance(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $finance = User::factory()->forRole(UserRole::Finance)->create();
        $customer = app(CustomerService::class)->create([
            'name' => 'Pelanggan Baca Saja',
            'type' => CustomerType::Individual->value,
        ], $admin);

        $this->actingAs($finance)
            ->get(route('customers.edit', $customer))
            ->assertForbidden();
    }

    #[Test]
    public function it_lists_customers_when_admin_opens_the_index_page(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $service = app(CustomerService::class);
        $service->create(['name' => 'Pelanggan Satu', 'type' => CustomerType::Individual->value], $admin);
        $service->create(['name' => 'Pelanggan Dua', 'type' => CustomerType::Company->value], $admin);

        $response = $this->actingAs($admin)->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('Pelanggan Satu');
        $response->assertSee('Pelanggan Dua');
        $response->assertSee(sprintf('CUS-%d-0001', now()->year));
    }

    #[Test]
    public function it_updates_customer_data_without_changing_the_code_when_admin_submits_changes(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = app(CustomerService::class)->create([
            'name' => 'Nama Lama',
            'type' => CustomerType::Individual->value,
        ], $admin);

        $response = $this->actingAs($admin)->put(route('customers.update', $customer), [
            'name' => 'Nama Baru',
            'type' => CustomerType::Company->value,
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('customers.show', $customer));
        $customer->refresh();
        $this->assertSame('Nama Baru', $customer->name);
        $this->assertSame(CustomerType::Company, $customer->type);
        $this->assertFalse($customer->is_active);
        $this->assertSame(sprintf('CUS-%d-0001', now()->year), $customer->code);

        $audit = AuditLog::query()->where('action', 'customer.updated')->firstOrFail();
        $this->assertSame('Nama Lama', $audit->before['name']);
        $this->assertSame('Nama Baru', $audit->after['name']);
    }

    #[Test]
    public function it_deletes_a_customer_with_a_reason_when_owner_requests_it(): void
    {
        $owner = User::factory()->forRole(UserRole::Owner)->create();
        $customer = app(CustomerService::class)->create([
            'name' => 'Pelanggan Hapus',
            'type' => CustomerType::Individual->value,
        ], $owner);

        $response = $this->actingAs($owner)->delete(route('customers.destroy', $customer), [
            'reason' => 'Pelanggan duplikat, hapus permanen.',
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'customer.deleted',
            'model_type' => Customer::class,
            'model_id' => $customer->id,
            'reason' => 'Pelanggan duplikat, hapus permanen.',
        ]);
    }

    #[Test]
    public function it_forbids_deletion_when_actor_is_admin(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = app(CustomerService::class)->create([
            'name' => 'Pelanggan Utuh',
            'type' => CustomerType::Individual->value,
        ], $admin);

        $response = $this->actingAs($admin)->delete(route('customers.destroy', $customer), [
            'reason' => 'Admin mencoba menghapus permanen.',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }
}
