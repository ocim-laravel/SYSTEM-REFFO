<?php

namespace App\Http\Requests\Order;

use App\Enums\ItemCategory;
use App\Enums\Priority;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pembuatan order + item dinamis (MOD-02, BR-01, BR-03).
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
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.category' => ['required', Rule::enum(ItemCategory::class)],
            'items.*.specification' => ['nullable', 'array'],
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
}
