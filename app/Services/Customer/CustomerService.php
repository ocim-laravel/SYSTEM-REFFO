<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\User;
use App\Services\System\AuditLogService;
use App\Services\System\NumberGeneratorService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Business logic pelanggan (MOD-01, AC-01-1).
 *
 * Kode pelanggan WAJIB diambil dari NumberGeneratorService (ADR-002),
 * bukan dari input pengguna. Nomor yang dibatalkan tidak dipakai ulang.
 */
class CustomerService
{
    public function __construct(
        private readonly NumberGeneratorService $numbers,
        private readonly AuditLogService $audit,
    ) {}

    /**
     * Pencarian & filter daftar pelanggan (AC-01-3, MOD-01).
     *
     * Filter yang didukung: nama, kode, telepon (pencocokan sebagian),
     * tipe, dan status aktif/nonaktif.
     *
     * @param  array{name?: string|null, code?: string|null, phone?: string|null, type?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<int, Customer>
     */
    public function search(array $filters = []): LengthAwarePaginator
    {
        $name = $filters['name'] ?? null;
        $code = $filters['code'] ?? null;
        $phone = $filters['phone'] ?? null;
        $type = $filters['type'] ?? null;
        $status = $filters['status'] ?? null;

        return Customer::query()
            ->when($name !== null && $name !== '', fn ($query) => $query->where('name', 'like', '%'.$name.'%'))
            ->when($code !== null && $code !== '', fn ($query) => $query->where('code', 'like', '%'.$code.'%'))
            ->when($phone !== null && $phone !== '', fn ($query) => $query->where('phone', 'like', '%'.$phone.'%'))
            ->when($type !== null && $type !== '', fn ($query) => $query->where('type', $type))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
    }

    /**
     * @param  array{name: string, type: string, phone?: string|null, whatsapp?: string|null, email?: string|null, address?: string|null, notes?: string|null, is_active?: bool}  $data
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function create(array $data, User $actor, array $meta = []): Customer
    {
        return DB::transaction(function () use ($data, $actor, $meta): Customer {
            $customer = Customer::create([
                'code' => $this->numbers->nextCustomer(),
                'name' => $data['name'],
                'type' => $data['type'],
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $actor->id,
            ]);

            $this->audit->record(
                action: 'customer.created',
                actor: $actor,
                modelType: Customer::class,
                modelId: $customer->id,
                after: $this->snapshot($customer),
                ip: $meta['ip'] ?? null,
                userAgent: $meta['user_agent'] ?? null,
            );

            return $customer;
        });
    }

    /**
     * @param  array{name: string, type: string, phone?: string|null, whatsapp?: string|null, email?: string|null, address?: string|null, notes?: string|null, is_active?: bool}  $data
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function update(Customer $customer, array $data, User $actor, array $meta = []): Customer
    {
        $before = $this->snapshot($customer);

        DB::transaction(function () use ($customer, $data, $actor, $meta, $before): void {
            $customer->update([
                'name' => $data['name'],
                'type' => $data['type'],
                'phone' => $data['phone'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => array_key_exists('is_active', $data)
                    ? (bool) $data['is_active']
                    : (bool) $customer->is_active,
            ]);
            $customer->refresh();

            $this->audit->record(
                action: 'customer.updated',
                actor: $actor,
                modelType: Customer::class,
                modelId: $customer->id,
                before: $before,
                after: $this->snapshot($customer),
                ip: $meta['ip'] ?? null,
                userAgent: $meta['user_agent'] ?? null,
            );
        });

        return $customer;
    }

    /**
     * Hapus permanen hanya oleh Owner; alasan wajib dicatat (BR-17, BR-18).
     *
     * @param  array{ip?: string|null, user_agent?: string|null}  $meta
     */
    public function delete(Customer $customer, string $reason, User $actor, array $meta = []): void
    {
        DB::transaction(function () use ($customer, $reason, $actor, $meta): void {
            $before = $this->snapshot($customer);
            $id = $customer->id;

            $customer->delete();

            $this->audit->record(
                action: 'customer.deleted',
                actor: $actor,
                modelType: Customer::class,
                modelId: $id,
                before: $before,
                reason: $reason,
                ip: $meta['ip'] ?? null,
                userAgent: $meta['user_agent'] ?? null,
            );
        });
    }

    /**
     * Snapshot audit tanpa data sensitif.
     *
     * @return array<string, mixed>
     */
    private function snapshot(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'code' => $customer->code,
            'name' => $customer->name,
            'type' => $customer->type?->value,
            'phone' => $customer->phone,
            'whatsapp' => $customer->whatsapp,
            'email' => $customer->email,
            'address' => $customer->address,
            'notes' => $customer->notes,
            'is_active' => (bool) $customer->is_active,
            'created_by' => $customer->created_by,
        ];
    }
}
