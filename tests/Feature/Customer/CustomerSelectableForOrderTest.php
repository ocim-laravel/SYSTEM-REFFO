<?php

namespace Tests\Feature\Customer;

use App\Enums\CustomerType;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use App\Services\Customer\CustomerService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guard pemilihan pelanggan untuk order baru (MOD-01, AC-01-2).
 *
 * Order sendiri belum ada (MOD-02 / PH-02); yang diuji di sini hanya
 * scope aktif dan penolakan pelanggan nonaktif di service.
 */
class CustomerSelectableForOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function customer(User $actor, string $name, bool $isActive): Customer
    {
        return app(CustomerService::class)->create([
            'name' => $name,
            'type' => CustomerType::Individual->value,
            'is_active' => $isActive,
        ], $actor);
    }

    #[Test]
    public function it_allows_an_active_customer_when_selecting_a_customer_for_a_new_order(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->customer($admin, 'Pelanggan Aktif', true);

        app(CustomerService::class)->assertSelectableForOrder($customer);

        $this->assertTrue(Customer::query()->active()->whereKey($customer->id)->exists());
    }

    #[Test]
    public function it_rejects_an_inactive_customer_when_selecting_a_customer_for_a_new_order(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->customer($admin, 'Pelanggan Nonaktif', false);

        try {
            app(CustomerService::class)->assertSelectableForOrder($customer);
            $this->fail('Pelanggan nonaktif seharusnya ditolak.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('customer_id', $exception->errors());
            $this->assertStringContainsString('nonaktif', $exception->errors()['customer_id'][0]);
        }
    }

    #[Test]
    public function it_excludes_inactive_customers_when_the_active_scope_is_applied(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $active = $this->customer($admin, 'Pelanggan Aktif', true);
        $this->customer($admin, 'Pelanggan Nonaktif', false);

        $ids = Customer::query()->active()->orderBy('id')->pluck('id')->all();

        $this->assertSame([$active->id], $ids);
    }
}
