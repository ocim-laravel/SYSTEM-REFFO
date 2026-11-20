<?php

namespace App\Http\Requests\Design;

use App\Models\OrderItem;
use Illuminate\Foundation\Http\FormRequest;

class SubmitDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof OrderItem
            && (bool) $this->user()?->can('submit', $item);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
