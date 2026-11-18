<?php

namespace App\Http\Requests\System;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('settings.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_name' => $this->filled('company_name')
                ? trim((string) $this->input('company_name'))
                : null,
            'remove_logo' => $this->boolean('remove_logo'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:120'],
            'company_logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_logo' => ['boolean'],
            'work_hours_start' => ['required', 'date_format:H:i'],
            'work_hours_end' => [
                'required',
                'date_format:H:i',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $start = $this->input('work_hours_start');

                    if (is_string($start) && is_string($value) && strcmp($value, $start) <= 0) {
                        $fail('Jam selesai harus setelah jam mulai.');
                    }
                },
            ],
        ];
    }
}
