<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('customers.delete');
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('reason')) {
            $this->merge(['reason' => trim((string) $this->input('reason'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
