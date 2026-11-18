<?php

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

class RealtimeTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
