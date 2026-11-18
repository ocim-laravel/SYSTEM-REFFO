<?php

namespace App\Http\Requests\System;

use App\Enums\OverridableRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi aksi override Owner (BR-18).
 *
 * Kolom reason wajib: tanpa alasan, request ditolak dengan 422 sebelum
 * service apa pun dipanggil.
 */
class OverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('system.override');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => $this->filled('reason') ? trim((string) $this->input('reason')) : $this->input('reason'),
            'before' => $this->decodeSnapshot('before'),
            'after' => $this->decodeSnapshot('after'),
        ]);
    }

    /**
     * Form mengirim before/after sebagai teks JSON; API/internal bisa mengirim array.
     * Nilai yang bukan JSON valid dibiarkan agar aturan `array` yang menolaknya.
     */
    private function decodeSnapshot(string $key): mixed
    {
        $value = $this->input($key);

        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rule' => ['required', Rule::enum(OverridableRule::class)],
            'target_type' => ['nullable', 'string', 'max:255'],
            'target_id' => ['nullable', 'integer', 'min:1'],
            'before' => ['nullable', 'array'],
            'after' => ['nullable', 'array'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan override wajib diisi.',
            'reason.min' => 'Alasan override minimal 5 karakter.',
            'rule.required' => 'Aturan yang di-override wajib dipilih.',
        ];
    }
}
