<?php

namespace App\Http\Requests\Design;

use App\Models\OrderItem;
use Illuminate\Foundation\Http\FormRequest;

class ReviseDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof OrderItem
            && (bool) $this->user()?->can('revise', $item);
    }

    protected function prepareForValidation(): void
    {
        $note = $this->input('note');
        $requestNote = $this->input('request_note');

        $value = is_string($note) && trim($note) !== '' ? $note : $requestNote;

        $this->merge([
            'note' => is_string($value) ? trim($value) : $value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'min:5', 'max:2000'],
            'request_note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note.required' => 'Catatan revisi wajib diisi.',
            'note.min' => 'Catatan revisi minimal 5 karakter.',
        ];
    }
}
