<?php

namespace App\Services\Delivery;

use App\Enums\DeliveryProgressStage;
use App\Enums\DeliveryStatus;
use App\Enums\DeliveryType;
use App\Enums\ItemStatus;
use App\Enums\OrderStatus;
use App\Models\Delivery;
use App\Models\DeliveryItem;
use App\Models\DeliveryProof;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Order\OrderService;
use App\Services\System\AuditLogService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Business logic pekerjaan lapangan (MOD-06, BR-13, BR-14, BR-17).
 *
 * - Pemasangan hanya setelah produksi selesai (BR-13).
 * - Biaya kirim/pasang diambil sekali dari orders.delivery_fee (BR-14).
 * - Setiap aksi penting ditulis ke audit log (BR-17).
 */
class DeliveryService
{
    private const PROOF_DISK = 'local';

    private const PROOF_DIRECTORY = 'delivery-proofs';

    public function __construct(
        private readonly OrderService $orders,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Buat jadwal kirim/pasang/ambil sendiri untuk satu order (MOD-06 fitur 1—3).
     *
     * @param  array{order_id: int|string, item_ids: array<int, mixed>, type?: DeliveryType|string|null, scheduled_at: mixed, address?: string|null, notes?: string|null, crew_ids?: array<int, mixed>}  $data
     *
     * @throws ValidationException
     */
    public function schedule(array $data, User $actor): Delivery
    {
        $order = Order::query()->findOrFail((int) $data['order_id']);
        $type = $this->type($data['type'] ?? null);
        $this->assertProductionFinished($order, $type);
        $items = $this->resolveItems($order, $data['item_ids'] ?? []);

        return DB::transaction(function () use ($order, $type, $items, $data, $actor): Delivery {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            $delivery = Delivery::create([
                'order_id' => $lockedOrder->id,
                'type' => $type,
                'scheduled_at' => Carbon::parse($data['scheduled_at']),
                'scheduled_by' => $actor->id,
                'crew' => $type === DeliveryType::Pickup ? [] : $this->crewIds($data['crew_ids'] ?? [], false),
                'status' => DeliveryStatus::Scheduled,
                'address' => $this->nullableText($data['address'] ?? null),
                'fee' => $this->feeForOrder($lockedOrder),
                'notes' => $this->nullableText($data['notes'] ?? null),
            ]);

            foreach ($items as $item) {
                DeliveryItem::create([
                    'delivery_id' => $delivery->id,
                    'order_item_id' => $item->id,
                ]);

                $this->orders->changeItemStatus(
                    $item->fresh(),
                    ItemStatus::Scheduled,
                    $actor,
                    'Item dijadwalkan untuk '.$type->label().'.',
                );
            }

            $lockedOrder->update([
                'status' => $type === DeliveryType::Installation
                    ? OrderStatus::Installing
                    : OrderStatus::Delivering,
            ]);

            $this->audit->record(
                action: 'delivery.scheduled',
                actor: $actor,
                modelType: Delivery::class,
                modelId: $delivery->id,
                after: $this->snapshot($delivery->fresh()),
            );

            return $delivery->fresh(['order.customer', 'items.orderItem', 'proofs', 'scheduler']);
        });
    }

    /**
     * Tugaskan petugas lapangan — pickup tidak memerlukan petugas (MOD-06 fitur 4).
     *
     * @param  array<int, mixed>  $crewIds
     *
     * @throws ValidationException
     */
    public function assignCrew(Delivery $delivery, array $crewIds, User $actor): Delivery
    {
        return DB::transaction(function () use ($delivery, $crewIds, $actor): Delivery {
            $locked = Delivery::query()->whereKey($delivery->id)->lockForUpdate()->firstOrFail();

            if ($locked->type === DeliveryType::Pickup) {
                throw ValidationException::withMessages([
                    'crew' => 'Jadwal ambil sendiri tidak memerlukan petugas lapangan.',
                ]);
            }

            if ($locked->status === DeliveryStatus::Completed) {
                throw ValidationException::withMessages([
                    'crew' => 'Jadwal yang sudah selesai tidak dapat diubah petugasnya.',
                ]);
            }

            $crew = $this->crewIds($crewIds);
            $before = $this->snapshot($locked);
            $locked->update(['crew' => $crew]);

            $this->audit->record(
                action: 'delivery.crew_assigned',
                actor: $actor,
                modelType: Delivery::class,
                modelId: $locked->id,
                before: $before,
                after: $this->snapshot($locked->fresh()),
            );

            return $locked->fresh(['order.customer', 'items.orderItem', 'proofs', 'scheduler']);
        });
    }

    /**
     * Catat progres berangkat/tiba oleh petugas lapangan (MOD-06 fitur 5).
     *
     * @throws ValidationException
     */
    public function recordProgress(
        Delivery $delivery,
        DeliveryProgressStage|string $stage,
        User $actor,
        ?string $note = null,
    ): Delivery {
        $stage = $stage instanceof DeliveryProgressStage
            ? $stage
            : DeliveryProgressStage::from((string) $stage);

        return DB::transaction(function () use ($delivery, $stage, $actor, $note): Delivery {
            $locked = Delivery::query()->whereKey($delivery->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === DeliveryStatus::Completed) {
                throw ValidationException::withMessages([
                    'stage' => 'Progres tidak dapat dicatat pada jadwal yang sudah selesai.',
                ]);
            }

            if ($locked->type !== DeliveryType::Pickup && ($locked->crew ?? []) === []) {
                throw ValidationException::withMessages([
                    'stage' => 'Petugas lapangan belum ditugaskan.',
                ]);
            }

            $before = $this->snapshot($locked);
            $updates = ['status' => DeliveryStatus::InProgress];

            if ($stage === DeliveryProgressStage::Departed) {
                $updates['departed_at'] = now();
            } else {
                $updates['arrived_at'] = now();
            }

            if ($note !== null && trim($note) !== '') {
                $updates['notes'] = $this->appendNote($locked->notes, trim($note));
            }

            $locked->update($updates);

            $itemStatus = $locked->type === DeliveryType::Installation
                ? ItemStatus::Installing
                : ItemStatus::Delivering;

            foreach ($this->itemIds($locked) as $itemId) {
                $item = OrderItem::query()->whereKey($itemId)->lockForUpdate()->first();
                if ($item !== null && in_array($item->status, [ItemStatus::Scheduled, ItemStatus::Delivering, ItemStatus::Installing], true)) {
                    $this->orders->changeItemStatus($item, $itemStatus, $actor, 'Progres lapangan: '.$stage->label().'.');
                }
            }

            $this->audit->record(
                action: 'delivery.progress_recorded',
                actor: $actor,
                modelType: Delivery::class,
                modelId: $locked->id,
                before: $before,
                after: array_merge($this->snapshot($locked->fresh()), ['stage' => $stage->value]),
            );

            return $locked->fresh(['order.customer', 'items.orderItem', 'proofs', 'scheduler']);
        });
    }

    /**
     * Unggah foto bukti + konfirmasi petugas REFFO (MOD-06 fitur 6).
     * Tidak ada tanda tangan/konfirmasi digital pelanggan.
     *
     * @throws ValidationException
     */
    public function attachProof(Delivery $delivery, UploadedFile $file, ?string $note, User $actor): DeliveryProof
    {
        return DB::transaction(function () use ($delivery, $file, $note, $actor): DeliveryProof {
            $locked = Delivery::query()->whereKey($delivery->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === DeliveryStatus::Completed) {
                throw ValidationException::withMessages([
                    'photo' => 'Bukti tidak dapat ditambahkan pada jadwal yang sudah selesai.',
                ]);
            }

            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
            $path = $file->storeAs(
                self::PROOF_DIRECTORY.'/'.$locked->id,
                Str::uuid()->toString().'.'.$extension,
                self::PROOF_DISK,
            );

            if ($path === false || $path === null) {
                throw ValidationException::withMessages([
                    'photo' => 'Gagal menyimpan foto bukti.',
                ]);
            }

            $proof = DeliveryProof::create([
                'delivery_id' => $locked->id,
                'photo_path' => $path,
                'note' => $this->nullableText($note),
                'uploaded_by' => $actor->id,
                'uploaded_at' => now(),
                'confirmed_by' => $actor->id,
                'confirmed_at' => now(),
            ]);

            $this->audit->record(
                action: 'delivery.proof_uploaded',
                actor: $actor,
                modelType: DeliveryProof::class,
                modelId: $proof->id,
                after: [
                    'delivery_id' => $locked->id,
                    'photo_path' => $path,
                    'uploaded_by' => $actor->id,
                    'confirmed_by' => $actor->id,
                ],
            );

            return $proof->load(['uploader', 'confirmer', 'delivery']);
        });
    }

    /**
     * Selesaikan pekerjaan lapangan (AC-06-3, AC-06-4, AC-06-5).
     *
     * @throws ValidationException
     */
    public function complete(Delivery $delivery, User $actor): Delivery
    {
        return DB::transaction(function () use ($delivery, $actor): Delivery {
            $locked = Delivery::query()->whereKey($delivery->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === DeliveryStatus::Completed) {
                throw ValidationException::withMessages([
                    'delivery' => 'Jadwal ini sudah diselesaikan.',
                ]);
            }

            $proofs = DeliveryProof::query()->where('delivery_id', $locked->id)->get();

            if ($locked->type !== DeliveryType::Pickup && $proofs->isEmpty()) {
                throw ValidationException::withMessages([
                    'photo' => 'Minimal satu foto bukti wajib diunggah sebelum pekerjaan diselesaikan (AC-06-3).',
                ]);
            }

            if ($locked->type !== DeliveryType::Pickup && ($locked->crew ?? []) === []) {
                throw ValidationException::withMessages([
                    'crew' => 'Petugas lapangan belum ditugaskan.',
                ]);
            }

            $before = $this->snapshot($locked);
            $locked->update([
                'status' => DeliveryStatus::Completed,
                'completed_by' => $actor->id,
                'completed_at' => now(),
            ]);

            foreach ($this->itemIds($locked) as $itemId) {
                $item = OrderItem::query()->whereKey($itemId)->lockForUpdate()->first();
                if ($item !== null && $item->status !== ItemStatus::Completed) {
                    $this->orders->changeItemStatus(
                        $item,
                        ItemStatus::Completed,
                        $actor,
                        'Pekerjaan lapangan selesai; bukti tercatat.',
                    );
                }
            }

            $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->firstOrFail();
            $stillOpen = $order->items()
                ->whereNotIn('status', [ItemStatus::Completed->value, ItemStatus::Cancelled->value])
                ->exists();

            if (! $stillOpen && $order->status !== OrderStatus::Completed) {
                $order->update(['status' => OrderStatus::Completed]);
            }

            $this->audit->record(
                action: 'delivery.completed',
                actor: $actor,
                modelType: Delivery::class,
                modelId: $locked->id,
                before: $before,
                after: array_merge($this->snapshot($locked->fresh()), [
                    'proofs' => $proofs->count(),
                    'completed_by' => $actor->id,
                    'order_status' => $order->fresh()?->status?->value,
                ]),
            );

            return $locked->fresh(['order.customer', 'items.orderItem', 'proofs.uploader', 'proofs.confirmer', 'scheduler', 'completer']);
        });
    }

    /**
     * Total biaya kirim/pasang yang benar-benar ditagih untuk satu order (BR-14).
     */
    public function chargedFee(Order $order): string
    {
        $total = '0.00';

        foreach (Delivery::query()->where('order_id', $order->id)->pluck('fee') as $fee) {
            $total = bcadd($total, (string) $fee, 2);
        }

        return $total;
    }

    /**
     * BR-13 + integritas #5: pemasangan hanya bila seluruh produksi selesai.
     *
     * @throws ValidationException
     */
    private function assertProductionFinished(Order $order, DeliveryType $type): void
    {
        $items = $order->items()->get()->filter(
            fn (OrderItem $item): bool => $item->status !== ItemStatus::Cancelled,
        );

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'item_ids' => 'Order tidak memiliki item yang dapat dijadwalkan.',
            ]);
        }

        foreach ($items as $item) {
            // Status sesudah produksi juga sah: item yang sudah dijadwalkan/dikerjakan
            // tetap membuktikan produksinya selesai (BR-13).
            if (in_array($item->status, [
                ItemStatus::Printed,
                ItemStatus::Packed,
                ItemStatus::Scheduled,
                ItemStatus::Delivering,
                ItemStatus::Installing,
            ], true)) {
                continue;
            }

            $message = 'Semua item harus selesai produksi (sudah cetak/dikemas) sebelum dijadwalkan.'
                .' Item "'.$item->title.'" masih '.$item->status?->label().'.';

            if ($type === DeliveryType::Installation) {
                $message = 'Pemasangan hanya dapat dijadwalkan setelah produksi selesai (BR-13).'
                    .' Item "'.$item->title.'" masih '.$item->status?->label().'.';
            }

            throw ValidationException::withMessages([
                'type' => $message,
            ]);
        }
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return Collection<int, OrderItem>
     */
    private function resolveItems(Order $order, array $ids): Collection
    {
        $unique = array_values(array_unique(array_map('intval', $ids)));

        if ($unique === []) {
            throw ValidationException::withMessages([
                'item_ids' => 'Minimal satu item harus dipilih.',
            ]);
        }

        $items = OrderItem::query()
            ->where('order_id', $order->id)
            ->whereIn('id', $unique)
            ->get();

        if ($items->count() !== count($unique)) {
            throw ValidationException::withMessages([
                'item_ids' => 'Sebagian item tidak ditemukan dalam order ini.',
            ]);
        }

        foreach ($items as $item) {
            if (! in_array($item->status, [ItemStatus::Printed, ItemStatus::Packed], true)) {
                throw ValidationException::withMessages([
                    'item_ids' => 'Item yang sudah dijadwalkan tidak boleh dijadwalkan ulang.',
                ]);
            }
        }

        return $items;
    }

    /**
     * BR-14: biaya diambil sekali dari order; jadwal berikutnya tidak menagih lagi.
     */
    private function feeForOrder(Order $order): string
    {
        $alreadyCharged = Delivery::query()
            ->where('order_id', $order->id)
            ->where('fee', '>', 0)
            ->exists();

        return $alreadyCharged ? '0.00' : (string) $order->delivery_fee;
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return list<int>
     */
    private function crewIds(array $ids, bool $requireFieldRole = true): array
    {
        $unique = array_values(array_unique(array_map('intval', $ids)));

        if ($unique === [] && $requireFieldRole) {
            throw ValidationException::withMessages([
                'crew' => 'Minimal satu petugas lapangan wajib ditugaskan.',
            ]);
        }

        if ($unique === []) {
            return [];
        }

        $users = User::query()->whereIn('id', $unique)->get();

        if ($users->count() !== count($unique)) {
            throw ValidationException::withMessages([
                'crew' => 'Sebagian petugas lapangan tidak ditemukan.',
            ]);
        }

        if ($requireFieldRole) {
            foreach ($users as $user) {
                if (! $user->can('deliveries.field')) {
                    throw ValidationException::withMessages([
                        'crew' => 'Pengguna yang dipilih bukan petugas lapangan yang berwenang.',
                    ]);
                }
            }
        }

        return $unique;
    }

    /**
     * @return list<int>
     */
    private function itemIds(Delivery $delivery): array
    {
        return DeliveryItem::query()
            ->where('delivery_id', $delivery->id)
            ->pluck('order_item_id')
            ->all();
    }

    private function type(DeliveryType|string|null $value): DeliveryType
    {
        if ($value instanceof DeliveryType) {
            return $value;
        }

        return $value === null || $value === ''
            ? DeliveryType::Delivery
            : DeliveryType::from($value);
    }

    private function nullableText(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    private function appendNote(?string $current, string $addition): string
    {
        return $current === null || $current === '' ? $addition : $current."\n".$addition;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshot(Delivery $delivery): array
    {
        return [
            'id' => $delivery->id,
            'order_id' => $delivery->order_id,
            'type' => $delivery->type?->value,
            'status' => $delivery->status?->value,
            'scheduled_at' => $delivery->scheduled_at?->format('Y-m-d H:i:s'),
            'crew' => $delivery->crew ?? [],
            'fee' => (string) $delivery->fee,
            'departed_at' => $delivery->departed_at?->format('Y-m-d H:i:s'),
            'arrived_at' => $delivery->arrived_at?->format('Y-m-d H:i:s'),
            'completed_at' => $delivery->completed_at?->format('Y-m-d H:i:s'),
        ];
    }
}
