<?php

namespace Tests\Feature\Order;

use App\Enums\CustomerType;
use App\Enums\ItemCategory;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use App\Services\Customer\CustomerService;
use App\Services\Order\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * AC-02-8 — ringkasan order (PDF) memuat pelanggan, item, total, deadline
 * (MOD-02, PH-02).
 */
class OrderPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function order(User $actor): Order
    {
        $customer = app(CustomerService::class)->create([
            'name' => 'PT Pelanggan PDF',
            'type' => CustomerType::Company->value,
            'phone' => '081234567890',
            'address' => 'Jl. Contoh 123',
            'is_active' => true,
        ], $actor);

        return app(OrderService::class)->create([
            'customer_id' => $customer->id,
            'discount' => 25000,
            'delivery_fee' => 15000,
            'notes' => 'Catatan PDF uji.',
            'deadline_at' => '2026-11-25 10:00:00',
            'items' => [
                [
                    'title' => 'Banner Acara',
                    'category' => ItemCategory::Printing->value,
                    'qty' => 2,
                    'unit' => 'pcs',
                    'unit_price' => 50000,
                    'priority' => 'normal',
                ],
            ],
        ], $actor);
    }

    #[Test]
    public function it_downloads_a_pdf_summary_containing_customer_name_and_total_for_authorized_user(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->order($admin);

        $response = $this->actingAs($admin)->get(route('orders.pdf', $order));

        $response->assertOk();
        $response->assertDownload($order->code.'.pdf');
        $this->assertStringStartsWith(
            'application/pdf',
            (string) $response->baseResponse->headers->get('content-type'),
        );

        $content = $this->decodedContent($response->baseResponse->getContent());

        $this->assertStringContainsString('PT Pelanggan PDF', $content);
        $this->assertStringContainsString('90.000,00', $content);
        $this->assertStringContainsString('ORD-', $content);
    }

    #[Test]
    public function it_shows_the_pdf_button_on_the_order_detail_page(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->order($admin);

        $this->actingAs($admin)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Unduh Ringkasan (PDF)');
    }

    #[Test]
    public function it_forbids_pdf_export_for_users_without_order_view_permission(): void
    {
        $admin = User::factory()->forRole(UserRole::Admin)->create();
        $order = $this->order($admin);

        $field = User::factory()->forRole(UserRole::Field)->create();

        $this->actingAs($field)->get(route('orders.pdf', $order))->assertForbidden();
    }

    private function decodedContent(string $pdf): string
    {
        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $matches) === 0) {
            return $pdf;
        }

        $decoded = '';
        foreach ($matches[1] as $stream) {
            $plain = @gzuncompress($stream);
            $decoded .= $plain === false ? $stream : $plain;
        }

        return $decoded;
    }
}
