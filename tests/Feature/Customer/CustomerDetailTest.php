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
 * Halaman detail pelanggan (MOD-01, AC-01-4): profil lengkap + tab Riwayat Order.
 */
class CustomerDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function completeCustomer(User $actor, bool $isActive = true): Customer
    {
        return app(CustomerService::class)->create([
            'name' => 'PT Reffo Media',
            'type' => CustomerType::Company->value,
            'phone' => '021-5550001',
            'whatsapp' => '081200000001',
            'email' => 'kontak@reffo.test',
            'address' => 'Jl. Merdeka No. 10, Jakarta',
            'notes' => 'Catatan internal pelanggan',
            'is_active' => $isActive,
        ], $actor);
    }

    #[Test]
    public function it_shows_the_complete_customer_profile_when_admin_opens_the_detail_page(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->completeCustomer($admin);

        $response = $this->actingAs($admin)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee($customer->code);
        $response->assertSee('PT Reffo Media');
        $response->assertSee('Perusahaan');
        $response->assertSee('021-5550001');
        $response->assertSee('081200000001');
        $response->assertSee('kontak@reffo.test');
        $response->assertSee('Jl. Merdeka No. 10, Jakarta');
        $response->assertSee('Catatan internal pelanggan');
        $response->assertSee('<th>Status</th><td>Aktif</td>', false);
    }

    #[Test]
    public function it_shows_the_inactive_status_when_the_customer_is_deactivated(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->completeCustomer($admin, isActive: false);

        $this->actingAs($admin)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('<th>Status</th><td>Nonaktif</td>', false);
    }

    #[Test]
    public function it_shows_the_order_history_empty_state_when_the_customer_has_no_order(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->completeCustomer($admin);

        $response = $this->actingAs($admin)->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('Riwayat Order');
        $response->assertSee('Belum ada order');
    }

    #[Test]
    public function it_shows_the_customer_profile_when_actor_is_finance(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $finance = User::factory()->forRole(UserRole::Finance)->create();
        $customer = $this->completeCustomer($admin);

        $this->actingAs($finance)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('PT Reffo Media')
            ->assertDontSee('Ubah Data');
    }
}
