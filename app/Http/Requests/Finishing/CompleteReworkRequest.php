<?php

namespace App\Http\Requests\Finishing;

use Illuminate\Foundation\Http\FormRequest;

class CompleteReworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string'],
        ];
    }
}
