<?php

namespace App\Http\Requests\Customer;

use App\Enums\CustomerType;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi parameter pencarian & filter daftar pelanggan (AC-01-3, MOD-01).
 */
class CustomerFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', Customer::class);
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        foreach (['name', 'code', 'phone'] as $field) {
            if ($this->filled($field)) {
                $merged[$field] = trim((string) $this->input($field));
            }
        }

        $this->merge($merged);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32'],
            'phone' => ['nullable', 'string', 'max:32'],
            'type' => ['nullable', Rule::enum(CustomerType::class)],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
