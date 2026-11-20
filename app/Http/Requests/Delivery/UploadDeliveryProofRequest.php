<?php

namespace App\Http\Requests\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class UploadDeliveryProofRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('attachProof', $this->route('delivery'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.required' => 'Foto bukti wajib diunggah.',
            'photo.mimes' => 'Foto bukti harus berformat JPG atau PNG.',
        ];
    }
}
