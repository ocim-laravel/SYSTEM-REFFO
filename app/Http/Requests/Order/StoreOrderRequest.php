<?php

namespace App\Http\Requests\Order;

use App\Enums\ActionType;
use App\Enums\FinishingType;
use App\Enums\ItemCategory;
use App\Enums\Priority;
use App\Models\Order;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pembuatan order + item dinamis (MOD-02, BR-01, BR-03, ADR-007 revisi UI).
 */
class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Order::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'priority' => ['nullable', Rule::enum(Priority::class)],
            'deadline_at' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'services' => ['nullable', 'array'],
            'services.printing' => ['nullable', 'boolean'],
            'services.kirim' => ['nullable', 'boolean'],
            'services.reklame' => ['nullable', 'boolean'],
            'reklame_actions' => ['nullable', 'array'],
            'reklame_actions.*' => ['nullable', 'string', Rule::enum(ActionType::class)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.category' => ['required', Rule::enum(ItemCategory::class)],
            'items.*.specification' => ['nullable', 'array'],
            'items.*.specification.mode' => ['nullable', Rule::in(['pcs', 'm2'])],
            'items.*.specification.bahan' => ['nullable', 'string', 'max:255'],
            'items.*.specification.ukuran_panjang' => ['nullable', 'numeric', 'gt:0'],
            'items.*.specification.ukuran_lebar' => ['nullable', 'numeric', 'gt:0'],
            'items.*.specification.quantity_asli' => ['nullable', 'numeric', 'gt:0'],
            'items.*.specification.luas_m2' => ['nullable', 'numeric', 'gt:0'],
            'items.*.specification.jenis_finishing' => ['nullable', Rule::enum(FinishingType::class)],
            'items.*.specification.tindakan' => ['nullable', 'array'],
            'items.*.specification.tindakan.*' => ['nullable', 'string', Rule::enum(ActionType::class)],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.deadline_at' => ['nullable', 'date'],
            'items.*.priority' => ['nullable', Rule::enum(Priority::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Order harus memiliki minimal 1 item.',
            'items.min' => 'Order harus memiliki minimal 1 item.',
        ];
    }

    /**
     * Validasi lintas field layanan: alamat hanya untuk order dengan layanan
     * Kirim; tindakan hanya untuk order dengan layanan Reklame; field wajib
     * mode m² harus valid (BR-03, ADR-007 §2–§5).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $services = $this->input('services', []);
            $serviceFlags = is_array($services) ? $services : [];
            $kirim = (bool) ($serviceFlags['kirim'] ?? false);
            $reklame = (bool) ($serviceFlags['reklame'] ?? false);

            if (! $kirim && $this->filled('delivery_address')) {
                $validator->errors()->add('delivery_address', 'Alamat hanya dapat diisi ketika layanan Kirim dipilih.');
            }

            $actions = $this->input('reklame_actions', []);
            $actionList = is_array($actions) ? array_values(array_filter($actions, fn ($action) => (bool) $action)) : [];
            if (! $reklame && $actionList !== []) {
                $validator->errors()->add('reklame_actions', 'Tindakan hanya dapat dipilih ketika layanan Reklame dipilih.');
            }

            foreach ($this->input('items', []) as $index => $item) {
                $specification = $item['specification'] ?? null;

                if (! is_array($specification) || ($specification['mode'] ?? null) !== 'm2') {
                    continue;
                }

                foreach (['ukuran_panjang', 'ukuran_lebar', 'quantity_asli'] as $field) {
                    $value = $specification[$field] ?? null;

                    if ($value === null || $value === '' || (is_numeric($value) && (float) $value <= 0)) {
                        $validator->errors()->add("items.{$index}.specification.{$field}", 'Item mode m² wajib memiliki panjang, lebar, dan quantity yang valid.');
                    }
                }
            }
        });
    }
}
