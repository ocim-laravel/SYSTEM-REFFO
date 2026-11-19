<?php

namespace App\Services\Order;

use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\OrderStatus;
use App\Enums\Priority;
use App\Models\Customer;
use App\Models\ItemStatusHistory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Customer\CustomerService;
use App\Services\System\AuditLogService;
use App\Services\System\NumberGeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Business logic order & item (MOD-02, PH-02) — domain saja, tanpa HTTP/UI.
 *
 * - Kode order wajib dari NumberGeneratorService::nextOrder() (ADR-002).
 * - Pelanggan wajib lolos CustomerService::assertSelectableForOrder() (AC-01-2).
 * - Total = Σ(qty × unit_price) − diskon + delivery_fee; pajak tidak masuk
 *   rumus (MOD-02 "Aturan penerapan"), biaya kirim sekali per order (BR-14).
 * - Status item diubah hanya lewat changeItemStatus() agar tiap perubahan
 *   menulis item_status_histories (integritas #7).
 */
class OrderService
{
    public function __construct(
        private readonly NumberGeneratorService $numbers,
        private readonly CustomerService $customers,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * @param  array{
     *     customer_id: int,
     *     items: list<array{title: string, category?: ItemCategory|string|null, specification?: array|null, qty?: int|float|string, unit?: string|null, unit_price?: int|float|string, deadline_at?: mixed, priority?: Priority|string|null}>,
     *     discount?: int|float|string,
     *     delivery_fee?: int|float|string,
     *     deadline_at?: mixed,
     *     priority?: Priority|string|null,
     *     notes?: string|null
     * }  $data
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function create(array $data, User $actor, array $meta = []): Order
    {
        /** @var Customer $customer */
        $customer = Customer::query()->findOrFail($data['customer_id']);
        $this->customers->assertSelectableForOrder($customer);

        return DB::transaction(function () use ($data, $actor, $customer, $meta): Order {
            $subtotal = '0.00';
            foreach ($data['items'] as $item) {
                $subtotal = bcadd($subtotal, $this->itemTotal($item), 2);
            }

            $discount = $this->amount($data['discount'] ?? 0);
            $deliveryFee = $this->amount($data['delivery_fee'] ?? 0);

            $order = Order::create([
                'code' => $this->numbers->nextOrder(),
                'customer_id' => $customer->id,
                'created_by' => $actor->id,
                'status' => OrderStatus::Draft,
                'priority' => $this->priority($data['priority'] ?? null),
                'deadline_at' => $data['deadline_at'] ?? null,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => '0.00',
                'total' => $this->grandTotal($subtotal, $discount, $deliveryFee),
                'delivery_fee' => $deliveryFee,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach (array_values($data['items']) as $index => $item) {
                $order->items()->create([
                    'seq' => $index + 1,
                    'title' => $item['title'],
                    'category' => $this->category($item['category'] ?? null),
                    'specification' => $item['specification'] ?? null,
                    'qty' => $this->amount($item['qty'] ?? 1),
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $this->amount($item['unit_price'] ?? 0),
                    'total' => $this->itemTotal($item),
                    'deadline_at' => $item['deadline_at'] ?? null,
                    'priority' => $this->priority($item['priority'] ?? null),
                    'status' => ItemStatus::Draft,
                ]);
            }

            $this->audit->record(
                action: 'order.created',
                actor: $actor,
                modelType: Order::class,
                modelId: $order->id,
                after: $this->orderSnapshot($order),
                ip: $meta['ip'] ?? null,
                userAgent: $meta['user_agent'] ?? null,
            );

            return $order;
        });
    }

    /**
     * Tambah item hanya saat order masih draft (MOD-02 "Aturan penerapan").
     *
     * @param  array{title: string, category?: ItemCategory|string|null, specification?: array|null, qty?: int|float|string, unit?: string|null, unit_price?: int|float|string, deadline_at?: mixed, priority?: Priority|string|null}  $data
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function addItem(Order $order, array $data, User $actor, array $meta = []): OrderItem
    {
        $this->assertOrderIsDraft($order);

        return DB::transaction(function () use ($order, $data, $actor, $meta): OrderItem {
            $seq = (int) $order->items()->max('seq') + 1;

            $item = $order->items()->create([
                'seq' => $seq,
                'title' => $data['title'],
                'category' => $this->category($data['category'] ?? null),
                'specification' => $data['specification'] ?? null,
                'qty' => $this->amount($data['qty'] ?? 1),
                'unit' => $data['unit'] ?? null,
                'unit_price' => $this->amount($data['unit_price'] ?? 0),
                'total' => $this->itemTotal($data),
                'deadline_at' => $data['deadline_at'] ?? null,
                'priority' => $this->priority($data['priority'] ?? null),
                'status' => ItemStatus::Draft,
            ]);

            $this->recalculate($order);

            $this->audit->record(
                action: 'order_item.created',
                actor: $actor,
                modelType: OrderItem::class,
                modelId: $item->id,
                after: $this->itemSnapshot($item),
                ip: $meta['ip'] ?? null,
                userAgent: $meta['user_agent'] ?? null,
            );

            return $item;
        });
    }

    /**
     * Satu-satunya jalur perubahan status item: selalu tulis riwayat (integritas #7).
     * Perubahan item A tidak menyentuh item B (BR-02).
     */
    public function changeItemStatus(
        OrderItem $item,
        ItemStatus $status,
        User $actor,
        ?string $note = null,
    ): OrderItem {
        if ($item->status === $status) {
            return $item;
        }

        $from = $item->status;

        DB::transaction(function () use ($item, $status, $from, $actor, $note): void {
            $item->update(['status' => $status]);

            ItemStatusHistory::create([
                'order_item_id' => $item->id,
                'from_status' => $from?->value,
                'to_status' => $status->value,
                'changed_by' => $actor->id,
                'note' => $note,
            ]);
        });

        return $item->refresh();
    }

    /**
     * Ubah harga satuan item; before/after wajib tercatat (BR-17).
     *
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function updateItemPrice(
        OrderItem $item,
        int|float|string $unitPrice,
        User $actor,
        array $meta = [],
    ): OrderItem {
        $order = $item->order;
        $this->assertOrderIsDraft($order);
        $before = $this->itemSnapshot($item);

        DB::transaction(function () use ($item, $order, $unitPrice, $actor, $meta, $before): void {
            $item->update([
                'unit_price' => $this->amount($unitPrice),
                'total' => bcmul($this->amount($item->qty), $this->amount($unitPrice), 2),
            ]);
            $item->refresh();

            $this->recalculate($order);

            $this->audit->record(
                action: 'order_item.price_updated',
                actor: $actor,
                modelType: OrderItem::class,
                modelId: $item->id,
                before: $before,
                after: $this->itemSnapshot($item),
                ip: $meta['ip'] ?? null,
                userAgent: $meta['user_agent'] ?? null,
            );
        });

        return $item;
    }

    /**
     * Hapus item — hanya saat order draft (integritas #1).
     *
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function deleteItem(OrderItem $item, User $actor, array $meta = []): void
    {
        $order = $item->order;
        $this->assertOrderIsDraft($order);
        $before = $this->itemSnapshot($item);

        DB::transaction(function () use ($item, $order, $actor, $meta, $before): void {
            $item->delete();
            $this->recalculate($order);

            $this->audit->record(
                action: 'order_item.deleted',
                actor: $actor,
                modelType: OrderItem::class,
                modelId: $item->id,
                before: $before,
                ip: $meta['ip'] ?? null,
                userAgent: $meta['user_agent'] ?? null,
            );
        });
    }

    /**
     * Hitung ulang subtotal & total dari item yang tersimpan.
     * Biaya kirim tetap satu kali di level order (BR-14).
     */
    public function recalculate(Order $order): Order
    {
        $subtotal = '0.00';
        foreach ($order->items()->get() as $item) {
            $subtotal = bcadd($subtotal, (string) $item->total, 2);
        }

        $order->update([
            'subtotal' => $subtotal,
            'total' => $this->grandTotal($subtotal, $order->discount, $order->delivery_fee),
        ]);

        return $order->refresh();
    }

    private function assertOrderIsDraft(Order $order): void
    {
        if ($order->status !== OrderStatus::Draft) {
            throw ValidationException::withMessages([
                'order' => 'Item hanya dapat diubah saat order berstatus draft.',
            ]);
        }
    }

    private function itemTotal(array $item): string
    {
        return bcmul(
            $this->amount($item['qty'] ?? 1),
            $this->amount($item['unit_price'] ?? 0),
            2,
        );
    }

    /**
     * Total order = Σ(item) − diskon + biaya kirim; pajak sengaja tidak masuk.
     */
    private function grandTotal(
        string $subtotal,
        int|float|string|null $discount,
        int|float|string|null $deliveryFee,
    ): string {
        return bcsub(
            bcadd($subtotal, $this->amount($deliveryFee), 2),
            $this->amount($discount),
            2,
        );
    }

    /**
     * Nominal uang dinormalisasi ke string 2 desimal sebelum aritmetika bcmath.
     */
    private function amount(int|float|string|null $value): string
    {
        if ($value === null) {
            return '0.00';
        }

        if (is_float($value)) {
            $value = number_format($value, 2, '.', '');
        }

        return bcadd((string) $value, '0', 2);
    }

    private function category(ItemCategory|string|null $value): ItemCategory
    {
        if ($value instanceof ItemCategory) {
            return $value;
        }

        return $value === null ? ItemCategory::Printing : ItemCategory::from($value);
    }

    private function priority(Priority|string|null $value): Priority
    {
        if ($value instanceof Priority) {
            return $value;
        }

        return $value === null ? Priority::Normal : Priority::from($value);
    }

    /**
     * @return array<string, mixed>
     */
    private function orderSnapshot(Order $order): array
    {
        return [
            'id' => $order->id,
            'code' => $order->code,
            'customer_id' => $order->customer_id,
            'status' => $order->status?->value,
            'subtotal' => (string) $order->subtotal,
            'discount' => (string) $order->discount,
            'tax' => (string) $order->tax,
            'total' => (string) $order->total,
            'delivery_fee' => (string) $order->delivery_fee,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function itemSnapshot(OrderItem $item): array
    {
        return [
            'id' => $item->id,
            'order_id' => $item->order_id,
            'seq' => $item->seq,
            'title' => $item->title,
            'qty' => (string) $item->qty,
            'unit_price' => (string) $item->unit_price,
            'total' => (string) $item->total,
            'status' => $item->status?->value,
        ];
    }
}
