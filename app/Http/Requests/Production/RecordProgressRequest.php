<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;

class RecordProgressRequest extends FormRequest
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
            'qty_planned' => ['nullable', 'numeric', 'min:0'],
            'qty_actual' => ['nullable', 'numeric', 'min:0'],
            'started_at' => ['nullable', 'date'],
            'finished_at' => ['nullable', 'date'],
            'mark_printed' => ['nullable', 'boolean'],
        ];
    }
}
