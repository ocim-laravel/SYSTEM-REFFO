<?php

namespace App\Http\Requests\Design;

use App\Models\OrderItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof OrderItem
            && (bool) $this->user()?->can('approve', $item);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'method' => ['sometimes', 'string', Rule::in(['internal', 'customer'])],
            'note' => ['nullable', 'string', 'max:1000'],
            'design_file_id' => ['nullable', 'integer', 'exists:design_files,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'method.in' => 'Metode ACC harus internal atau customer.',
            'note.max' => 'Catatan ACC maksimal 1000 karakter.',
        ];
    }
}
