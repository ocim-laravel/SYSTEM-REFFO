<?php

namespace App\Http\Requests\Design;

use Illuminate\Foundation\Http\FormRequest;

class UploadDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('designs.upload');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
