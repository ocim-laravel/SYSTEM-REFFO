<?php

namespace App\Http\Requests\System;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Otorisasi juga dilakukan di Form Request agar user tanpa izin
     * mendapat 403 sebelum validasi dijalankan.
     */
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof User
            ? (bool) $this->user()?->can('update', $target)
            : (bool) $this->user()?->can('create', User::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $target = $this->route('user');

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($target instanceof User ? $target->id : null),
            ],
            'role' => ['required', Rule::in(UserRole::values())],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'is_active' => ['boolean'],
        ];

        if (! $target instanceof User) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $rules;
    }
}
