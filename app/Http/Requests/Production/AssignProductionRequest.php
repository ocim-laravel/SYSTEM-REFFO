<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;

class AssignProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('production.assign') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'operator_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
