<?php

namespace Tests\Feature\Customer;

use App\Enums\CustomerType;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * AC-01-3 — pencarian nama/kode/telepon dan filter tipe/status (MOD-01).
 */
class CustomerSearchFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->forRole(UserRole::Admin)->create();

        Customer::query()->create([
            'code' => 'CUS-2026-0001',
            'name' => 'PT Sinar Jaya',
            'type' => CustomerType::Company,
            'phone' => '081200000001',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        Customer::query()->create([
            'code' => 'CUS-2026-0002',
            'name' => 'Budi Santoso',
            'type' => CustomerType::Individual,
            'phone' => '081200000002',
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);

        Customer::query()->create([
            'code' => 'CUS-2026-0003',
            'name' => 'CV Cahaya Abadi',
            'type' => CustomerType::Company,
            'phone' => '081200000003',
            'is_active' => false,
            'created_by' => $this->admin->id,
        ]);
    }

    /**
     * @param  array<string, string>  $query
     */
    private function filter(array $query): TestResponse
    {
        return $this->actingAs($this->admin)->get(route('customers.index', $query));
    }

    #[Test]
    public function it_filters_customers_by_partial_name_when_name_query_is_given(): void
    {
        $response = $this->filter(['name' => 'Sinar']);

        $response->assertOk();
        $response->assertSee('PT Sinar Jaya');
        $response->assertDontSee('Budi Santoso');
        $response->assertDontSee('CV Cahaya Abadi');
    }

    #[Test]
    public function it_filters_customers_by_partial_code_when_code_query_is_given(): void
    {
        $response = $this->filter(['code' => '0002']);

        $response->assertOk();
        $response->assertSee('Budi Santoso');
        $response->assertDontSee('PT Sinar Jaya');
        $response->assertDontSee('CV Cahaya Abadi');
    }

    #[Test]
    public function it_filters_customers_by_partial_phone_when_phone_query_is_given(): void
    {
        $response = $this->filter(['phone' => '0003']);

        $response->assertOk();
        $response->assertSee('CV Cahaya Abadi');
        $response->assertDontSee('PT Sinar Jaya');
        $response->assertDontSee('Budi Santoso');
    }

    #[Test]
    public function it_filters_customers_by_type_when_type_query_is_given(): void
    {
        $response = $this->filter(['type' => CustomerType::Company->value]);

        $response->assertOk();
        $response->assertSee('PT Sinar Jaya');
        $response->assertSee('CV Cahaya Abadi');
        $response->assertDontSee('Budi Santoso');
    }

    #[Test]
    public function it_filters_only_active_customers_when_status_query_is_active(): void
    {
        $response = $this->filter(['status' => 'active']);

        $response->assertOk();
        $response->assertSee('PT Sinar Jaya');
        $response->assertSee('Budi Santoso');
        $response->assertDontSee('CV Cahaya Abadi');
    }

    #[Test]
    public function it_filters_only_inactive_customers_when_status_query_is_inactive(): void
    {
        $response = $this->filter(['status' => 'inactive']);

        $response->assertOk();
        $response->assertSee('CV Cahaya Abadi');
        $response->assertDontSee('PT Sinar Jaya');
        $response->assertDontSee('Budi Santoso');
    }

    #[Test]
    public function it_combines_name_and_status_filters_when_both_queries_are_given(): void
    {
        $response = $this->filter(['name' => 'CV', 'status' => 'active']);

        $response->assertOk();
        $response->assertDontSee('CV Cahaya Abadi');
        $response->assertDontSee('PT Sinar Jaya');
    }

    #[Test]
    public function it_rejects_an_unknown_status_filter_when_status_query_is_invalid(): void
    {
        $this->filter(['status' => 'dihapus'])
            ->assertSessionHasErrors('status');
    }
}
