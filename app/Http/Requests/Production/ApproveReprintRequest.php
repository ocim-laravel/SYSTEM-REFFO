<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;

class ApproveReprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('production.reprint.approve') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'actual_qty' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
