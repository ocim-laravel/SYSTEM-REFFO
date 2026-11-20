<?php

namespace App\Http\Requests\Delivery;

use App\Enums\DeliveryProgressStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordDeliveryProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('recordProgress', $this->route('delivery'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'stage' => ['required', Rule::enum(DeliveryProgressStage::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
