<?php

namespace Tests\Feature\Customer;

use App\Enums\CustomerType;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use App\Services\Customer\CustomerService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Hak akses pelanggan (MOD-01): Admin & Owner penuh, Produksi & Finance baca saja.
 */
class CustomerAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function existingCustomer(): Customer
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        return app(CustomerService::class)->create([
            'name' => 'PT Pelanggan Terdaftar',
            'type' => CustomerType::Company->value,
        ], $admin);
    }

    #[Test]
    public function it_shows_the_customer_menu_and_read_only_list_when_actor_is_production(): void
    {
        $customer = $this->existingCustomer();
        $production = User::factory()->forRole(UserRole::Production)->create();

        $this->actingAs($production)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-menu="customer"', false);

        $this->actingAs($production)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('PT Pelanggan Terdaftar')
            ->assertDontSee('Tambah Pelanggan');

        $this->actingAs($production)
            ->get(route('customers.edit', $customer))
            ->assertForbidden();

        $this->actingAs($production)
            ->post(route('customers.store'), [
                'name' => 'Pelanggan Baru',
                'type' => CustomerType::Individual->value,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function it_shows_the_customer_menu_and_read_only_list_when_actor_is_finance(): void
    {
        $this->existingCustomer();
        $finance = User::factory()->forRole(UserRole::Finance)->create();

        $this->actingAs($finance)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-menu="customer"', false);

        $this->actingAs($finance)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('PT Pelanggan Terdaftar')
            ->assertDontSee('Tambah Pelanggan');

        $this->actingAs($finance)
            ->post(route('customers.store'), [
                'name' => 'Pelanggan Baru',
                'type' => CustomerType::Individual->value,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function it_shows_the_create_button_when_actor_is_admin(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-menu="customer"', false);

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Tambah Pelanggan');
    }

    #[Test]
    public function it_shows_the_create_button_when_actor_is_owner(): void
    {
        $owner = User::factory()->forRole(UserRole::Owner)->create();

        $this->actingAs($owner)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Tambah Pelanggan');
    }
}
