<?php

namespace App\Http\Requests\Delivery;

use App\Enums\DeliveryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('deliveries.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['required', 'integer', 'exists:order_items,id'],
            'type' => ['required', Rule::enum(DeliveryType::class)],
            'scheduled_at' => ['required', 'date'],
            'address' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'crew_ids' => ['nullable', 'array'],
            'crew_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'item_ids.min' => 'Minimal satu item harus dipilih.',
            'type.required' => 'Tipe pekerjaan lapangan wajib dipilih.',
        ];
    }
}
