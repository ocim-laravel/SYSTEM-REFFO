<?php

namespace App\Http\Requests\Finishing;

use Illuminate\Foundation\Http\FormRequest;

class AssignFinishingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finishing.assign') ?? false;
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
