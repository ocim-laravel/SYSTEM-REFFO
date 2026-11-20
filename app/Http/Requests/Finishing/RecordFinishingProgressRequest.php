<?php

namespace App\Http\Requests\Finishing;

use Illuminate\Foundation\Http\FormRequest;

class RecordFinishingProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finishing.progress') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'progress' => ['nullable', 'integer', 'min:0', 'max:100'],
            'progress_note' => ['nullable', 'string'],
            'started_at' => ['nullable', 'date'],
            'finished_at' => ['nullable', 'date'],
        ];
    }
}
