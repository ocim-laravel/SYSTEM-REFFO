<?php

namespace App\Http\Requests\Design;

use App\Models\OrderItem;
use Illuminate\Foundation\Http\FormRequest;

class ChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof OrderItem
            && (bool) $this->user()?->can('changeRequest', $item);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'cost_impact' => ['required', 'numeric', 'min:0'],
            'deadline_impact' => ['nullable', 'date'],
            'design_file_id' => ['nullable', 'integer', 'exists:design_files,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan Change Request wajib diisi.',
            'reason.min' => 'Alasan Change Request minimal 5 karakter.',
            'cost_impact.required' => 'Dampak biaya wajib diisi.',
            'cost_impact.numeric' => 'Dampak biaya harus berupa angka.',
            'cost_impact.min' => 'Dampak biaya tidak boleh negatif.',
        ];
    }
}
