<?php

namespace App\Http\Requests\System;

use App\Models\Division;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DivisionRequest extends FormRequest
{
    /**
     * Authorization juga dilakukan di Form Request agar user tanpa izin
     * mendapat 403 sebelum validasi dijalankan.
     */
    public function authorize(): bool
    {
        $division = $this->route('division');

        return $division instanceof Division
            ? (bool) $this->user()?->can('update', $division)
            : (bool) $this->user()?->can('create', Division::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $division = $this->route('division');

        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('divisions', 'code')->ignore($division instanceof Division ? $division->id : null),
            ],
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
