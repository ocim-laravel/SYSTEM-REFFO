<?php

namespace App\Http\Requests\Customer;

use App\Enums\CustomerType;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer instanceof Customer
            ? (bool) $this->user()?->can('update', $customer)
            : (bool) $this->user()?->can('create', Customer::class);
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        foreach (['name', 'phone', 'whatsapp', 'email', 'address', 'notes'] as $field) {
            if ($this->filled($field)) {
                $merged[$field] = trim((string) $this->input($field));
            }
        }

        if ($this->has('is_active')) {
            $merged['is_active'] = $this->boolean('is_active');
        }

        $this->merge($merged);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // `code` sengaja tidak ada: nomor dihasilkan server (AC-01-1, ADR-002).
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CustomerType::class)],
            'phone' => ['nullable', 'string', 'max:32'],
            'whatsapp' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
