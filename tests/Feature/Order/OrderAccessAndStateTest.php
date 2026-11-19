<?php

namespace Tests\Feature\Order;

use App\Enums\CustomerType;
use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use App\Services\Customer\CustomerService;
use App\Services\Order\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Akses route & state machine validasi order (MOD-02, PH-02 sesi 2).
 */
class OrderAccessAndStateTest extends TestCase
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
            'name' => 'PT Pelanggan Order',
            'type' => CustomerType::Company->value,
            'is_active' => true,
        ], $actor);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(Customer $customer, int $itemCount = 1): array
    {
        $items = [];
        for ($i = 1; $i <= $itemCount; $i++) {
            $items[] = [
                'title' => 'Item '.$i,
                'category' => ItemCategory::Printing->value,
                'qty' => 1,
                'unit' => 'pcs',
                'unit_price' => 10000,
            ];
        }

        return [
            'customer_id' => $customer->id,
            'items' => $items,
        ];
    }

    private function createPendingOrder(): Order
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();

        return app(OrderService::class)->create(
            $this->validPayload($this->activeCustomer($designer), 2),
            $designer,
        );
    }

    private function createDraftOrder(): Order
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        return app(OrderService::class)->create(
            $this->validPayload($this->activeCustomer($admin), 2),
            $admin,
        );
    }

    #[Test]
    public function it_creates_a_pending_validation_order_when_designer_submits_a_valid_draft(): void
    {
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->activeCustomer($admin);

        $response = $this->actingAs($designer)->post(route('orders.store'), $this->validPayload($customer, 2));

        $order = Order::query()->firstOrFail();

        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame(OrderStatus::PendingValidation, $order->status);
        $this->assertSame($designer->id, $order->created_by);
        $this->assertNull($order->validated_by);
        $this->assertSame(2, $order->items()->count());
        $this->assertTrue($order->items->every(fn ($item) => $item->status === ItemStatus::Draft));
    }

    #[Test]
    public function it_creates_a_draft_order_when_admin_creates_an_order(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->activeCustomer($admin);

        $response = $this->actingAs($admin)->post(route('orders.store'), $this->validPayload($customer, 2));

        $order = Order::query()->firstOrFail();

        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame(OrderStatus::Draft, $order->status);
        $this->assertSame($admin->id, $order->created_by);
        $this->assertNull($order->validated_by);
        $this->assertTrue($order->items->every(fn ($item) => $item->status === ItemStatus::Draft));
    }

    #[Test]
    public function it_validates_a_draft_order_created_by_admin(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->createDraftOrder();

        $this->assertSame(OrderStatus::Draft, $order->status);

        $response = $this->actingAs($admin)->post(route('orders.validate', $order));

        $response->assertRedirect(route('orders.show', $order));
        $order->refresh()->load('items');
        $this->assertSame(OrderStatus::Validated, $order->status);
        $this->assertSame($admin->id, $order->validated_by);
        $this->assertTrue($order->items->every(fn ($item) => $item->status === ItemStatus::QueuedDesign));
    }

    #[Test]
    public function it_forbids_order_validation_when_actor_is_designer(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $order = $this->createPendingOrder();

        $response = $this->actingAs($designer)->post(route('orders.validate', $order));

        $response->assertForbidden();
        $order->refresh();
        $this->assertSame(OrderStatus::PendingValidation, $order->status);
        $this->assertNull($order->validated_by);
        $this->assertTrue($order->items->every(fn ($item) => $item->fresh()->status === ItemStatus::Draft));
    }

    #[Test]
    public function it_validates_the_order_and_queues_all_items_for_design_when_actor_is_admin(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $customer = $this->activeCustomer($designer);
        $order = app(OrderService::class)->create($this->validPayload($customer, 3), $designer);

        $response = $this->actingAs($admin)->post(route('orders.validate', $order));

        $response->assertRedirect(route('orders.show', $order));
        $order->refresh()->load('items');

        $this->assertSame(OrderStatus::Validated, $order->status);
        $this->assertSame($admin->id, $order->validated_by);
        $this->assertNotNull($order->validated_at);
        $this->assertCount(3, $order->items);
        $this->assertTrue($order->items->every(fn ($item) => $item->status === ItemStatus::QueuedDesign));
        $this->assertSame(3, $order->items->sum(fn ($item) => $item->statusHistories()->count()));
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'order.validated',
            'model_type' => Order::class,
            'model_id' => $order->id,
            'user_id' => $admin->id,
        ]);
    }

    #[Test]
    public function it_forbids_order_validation_when_actor_is_owner(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $owner = User::factory()->forRole(UserRole::Owner)->create();
        $order = $this->createPendingOrder();

        $response = $this->actingAs($owner)->post(route('orders.validate', $order));

        $response->assertForbidden();
        $this->assertSame(OrderStatus::PendingValidation, $order->fresh()->status);
    }

    #[Test]
    public function it_rejects_store_when_order_has_no_items(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->activeCustomer($admin);

        $response = $this->actingAs($admin)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertSame(0, Order::query()->count());
    }

    #[Test]
    public function it_allows_index_create_store_and_show_for_admin_owner_and_designer(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->createPendingOrder();

        foreach ([UserRole::Admin, UserRole::Owner, UserRole::Designer] as $role) {
            $user = $role === UserRole::Admin
                ? $admin
                : User::factory()->forRole($role)->create();

            $this->actingAs($user)->get(route('orders.index'))->assertOk();
            $this->actingAs($user)->get(route('orders.create'))->assertOk();
            $this->actingAs($user)->get(route('orders.show', $order))->assertOk();
        }

        $designer = User::factory()->forRole(UserRole::Designer)->create();
        $customer = $this->activeCustomer($admin);
        $this->actingAs($designer)
            ->post(route('orders.store'), $this->validPayload($customer, 1))
            ->assertRedirect();
        $this->assertSame(2, Order::query()->count());
    }

    #[Test]
    public function it_forbids_order_routes_for_roles_without_orders_view_permission(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->createPendingOrder();

        foreach ([UserRole::Finance, UserRole::Hrd, UserRole::Production, UserRole::Field] as $role) {
            $user = User::factory()->forRole($role)->create();

            $this->actingAs($user)->get(route('orders.index'))->assertForbidden();
            $this->actingAs($user)->get(route('orders.create'))->assertForbidden();
            $this->actingAs($user)->get(route('orders.show', $order))->assertForbidden();
            $this->actingAs($user)->post(route('orders.validate', $order))->assertForbidden();
        }
    }

    #[Test]
    public function it_filters_orders_by_status_date_range_and_customer_on_index(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $c1 = $this->activeCustomer($admin);
        $c2 = app(CustomerService::class)->create([
            'name' => 'PT Lain',
            'type' => CustomerType::Company->value,
            'is_active' => true,
        ], $admin);

        $order1 = app(OrderService::class)->create(
            $this->validPayload($c1, 1),
            User::factory()->forRole(UserRole::Designer)->create(),
        ); // pending_validation
        $order2 = app(OrderService::class)->create($this->validPayload($c2, 1), $admin);
        app(OrderService::class)->validate($order2, $admin); // validated

        $this->actingAs($admin)
            ->get(route('orders.index', ['status' => OrderStatus::PendingValidation->value]))
            ->assertOk()
            ->assertSee($order1->code)
            ->assertDontSee($order2->code);

        $this->actingAs($admin)
            ->get(route('orders.index', ['customer_id' => $c2->id]))
            ->assertOk()
            ->assertSee($order2->code)
            ->assertDontSee($order1->code);

        $from = now()->subDay()->toDateString();
        $to = now()->addDay()->toDateString();
        $this->actingAs($admin)
            ->get(route('orders.index', ['date_from' => $from, 'date_to' => $to]))
            ->assertOk()
            ->assertSee($order1->code)
            ->assertSee($order2->code);
    }

    #[Test]
    public function it_shows_a_link_to_create_a_new_customer_on_the_order_create_page(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();

        $this->actingAs($admin)
            ->get(route('orders.create'))
            ->assertOk()
            ->assertSee('Tambah Pelanggan')
            ->assertSee(route('customers.create'))
            ->assertSee('target="_blank"', false);
    }

    #[Test]
    public function it_stores_a_printing_order_with_the_product_mode_item(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->activeCustomer($admin);

        $response = $this->actingAs($admin)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'services' => ['printing' => true],
            'items' => [
                [
                    'title' => 'Banner X Produk',
                    'category' => ItemCategory::Printing->value,
                    'specification' => [
                        'mode' => 'pcs',
                        'quantity_asli' => 3,
                    ],
                    'qty' => 3,
                    'unit' => 'pcs',
                    'unit_price' => 50000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $order = Order::query()->firstOrFail();
        $item = $order->items->firstOrFail();

        $this->assertSame('pcs', $item->specification['mode']);
        $this->assertSame('3.00', (string) $item->qty);
        $this->assertSame('pcs', $item->unit);
        $this->assertSame('150000.00', (string) $item->total);
    }

    #[Test]
    public function it_stores_a_printing_order_with_the_square_meter_mode_item_and_effective_area(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->activeCustomer($admin);

        $response = $this->actingAs($admin)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'services' => ['printing' => true],
            'items' => [
                [
                    'title' => 'Banner MMT',
                    'category' => ItemCategory::Printing->value,
                    'specification' => [
                        'mode' => 'm2',
                        'bahan' => 'Kain',
                        'ukuran_panjang' => 2,
                        'ukuran_lebar' => 1,
                        'quantity_asli' => 2,
                        'jenis_finishing' => 'potong',
                    ],
                    'qty' => 2,
                    'unit' => 'm2',
                    'unit_price' => 100000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $order = Order::query()->firstOrFail();
        $item = $order->items->firstOrFail();

        // Luas efektif = panjang (2) x lebar (1) x quantity (2) = 4 m².
        $this->assertSame('4.00', (string) $item->qty);
        $this->assertSame('m2', $item->unit);
        $this->assertSame('400000.00', (string) $item->total);
        $this->assertSame('4.00', (string) $item->specification['luas_m2']);
        $this->assertSame('2.00', (string) $item->specification['quantity_asli']);
        $this->assertSame('Kain', $item->specification['bahan']);
        $this->assertSame('2.00', (string) $item->specification['ukuran_panjang']);
        $this->assertSame('1.00', (string) $item->specification['ukuran_lebar']);
        $this->assertSame('potong', $item->specification['jenis_finishing']);
        $this->assertSame('m2', $item->specification['mode']);
    }

    #[Test]
    public function it_stores_the_delivery_address_when_the_kirim_service_is_checked(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->activeCustomer($admin);

        $this->actingAs($admin)
            ->get(route('orders.create'))
            ->assertOk()
            ->assertSee('Alamat Pengiriman/Pemasangan');

        $response = $this->actingAs($admin)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'services' => ['printing' => true, 'kirim' => true],
            'delivery_address' => 'Jl. Merdeka No. 12, Bandung',
            'items' => [
                [
                    'title' => 'Banner Kirim',
                    'category' => ItemCategory::Printing->value,
                    'qty' => 2,
                    'unit' => 'pcs',
                    'unit_price' => 50000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $order = Order::query()->firstOrFail();
        $item = $order->items->firstOrFail();

        $this->assertSame('Jl. Merdeka No. 12, Bandung', $item->specification['delivery_address']);
    }

    #[Test]
    public function it_does_not_store_the_delivery_address_when_the_kirim_service_is_not_checked(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->activeCustomer($admin);

        $response = $this->actingAs($admin)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'services' => ['printing' => true],
            'items' => [
                [
                    'title' => 'Banner Tanpa Kirim',
                    'category' => ItemCategory::Printing->value,
                    'qty' => 1,
                    'unit' => 'pcs',
                    'unit_price' => 50000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $order = Order::query()->firstOrFail();
        $item = $order->items->firstOrFail();

        $this->assertArrayNotHasKey('delivery_address', $item->specification ?? []);
    }

    #[Test]
    public function it_shows_reklame_action_fields_and_stores_the_selected_action(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->activeCustomer($admin);

        $this->actingAs($admin)
            ->get(route('orders.create'))
            ->assertOk()
            ->assertSee('Konstruksi/Rangka')
            ->assertSee('Pemasangan');

        $response = $this->actingAs($admin)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'services' => ['reklame' => true],
            'reklame_actions' => ['konstruksi_rangka'],
            'items' => [
                [
                    'title' => 'Spanduk Reklame',
                    'category' => ItemCategory::Reklame->value,
                    'qty' => 5,
                    'unit' => 'm2',
                    'unit_price' => 80000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $order = Order::query()->firstOrFail();
        $item = $order->items->firstOrFail();

        $this->assertSame(['konstruksi_rangka'], $item->specification['tindakan']);
    }

    #[Test]
    public function it_does_not_store_a_reklame_action_when_the_reklame_service_is_not_checked(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->activeCustomer($admin);

        $response = $this->actingAs($admin)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'title' => 'Spanduk Reklame',
                    'category' => ItemCategory::Reklame->value,
                    'qty' => 5,
                    'unit' => 'm2',
                    'unit_price' => 80000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $order = Order::query()->firstOrFail();
        $item = $order->items->firstOrFail();

        $this->assertArrayNotHasKey('tindakan', $item->specification ?? []);
    }

    #[Test]
    public function it_keeps_the_discount_calculated_after_subtotal_in_the_order_total(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->activeCustomer($admin);

        $response = $this->actingAs($admin)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'discount' => 50000,
            'delivery_fee' => 30000,
            'items' => [
                [
                    'title' => 'Kartu Nama',
                    'category' => ItemCategory::Printing->value,
                    'qty' => 2,
                    'unit' => 'pcs',
                    'unit_price' => 100000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $order = Order::query()->firstOrFail();

        $this->assertSame('200000.00', (string) $order->subtotal);
        $this->assertSame('50000.00', (string) $order->discount);
        $this->assertSame('30000.00', (string) $order->delivery_fee);
        $this->assertSame('180000.00', (string) $order->total);
    }

    #[Test]
    public function it_creates_a_minimal_order_with_a_single_item_using_the_previous_flow(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $customer = $this->activeCustomer($admin);

        $response = $this->actingAs($admin)->post(route('orders.store'), [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'title' => 'Item Satu',
                    'category' => ItemCategory::Printing->value,
                    'qty' => 1,
                    'unit' => 'pcs',
                    'unit_price' => 10000,
                ],
            ],
        ]);

        $response->assertRedirect();
        $order = Order::query()->firstOrFail();
        $item = $order->items->firstOrFail();

        $this->assertSame(1, $order->items()->count());
        $this->assertSame('10000.00', (string) $item->total);
        $this->assertNull($item->specification);
    }
}
