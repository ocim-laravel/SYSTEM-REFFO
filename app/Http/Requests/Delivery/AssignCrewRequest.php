<?php

namespace App\Http\Requests\Delivery;

use Illuminate\Foundation\Http\FormRequest;

class AssignCrewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('assignCrew', $this->route('delivery'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'crew_ids' => ['required', 'array', 'min:1'],
            'crew_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'crew_ids.min' => 'Minimal satu petugas lapangan wajib ditugaskan.',
        ];
    }
}
