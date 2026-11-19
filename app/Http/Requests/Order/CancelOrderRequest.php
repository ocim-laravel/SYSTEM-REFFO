<?php

namespace App\Http\Requests\Order;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi pembatalan order (AC-02-5, BR-15, BR-17).
 *
 * Alasan wajib, biaya aktual dan nilai kesepakatan dicatat terpisah.
 */
class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        return $order instanceof Order
            && (bool) $this->user()?->can('cancel', $order);
    }

    protected function prepareForValidation(): void
    {
        $reason = $this->input('reason');
        $note = $this->input('settlement_note');

        $this->merge([
            'reason' => is_string($reason) ? trim($reason) : $reason,
            'settlement_note' => is_string($note) && trim($note) !== '' ? trim($note) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'actual_cost' => ['required', 'numeric', 'min:0'],
            'settlement_amount' => ['required', 'numeric', 'min:0'],
            'settlement_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan pembatalan wajib diisi.',
            'reason.min' => 'Alasan pembatalan minimal 5 karakter.',
            'actual_cost.required' => 'Biaya aktual wajib diisi (isikan 0 bila belum ada biaya).',
            'settlement_amount.required' => 'Nilai kesepakatan wajib diisi (isikan 0 bila tidak ada).',
        ];
    }
}
