<?php

namespace App\Http\Requests\Finishing;

use App\Enums\QcResult;
use App\Enums\RedoStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordQcRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finishing.qc') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('notes') && is_string($this->input('notes'))) {
            $this->merge(['notes' => trim((string) $this->input('notes'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'result' => ['required', Rule::enum(QcResult::class)],
            'notes' => ['required_if:result,fail', 'nullable', 'string'],
            'redo_stage' => ['nullable', Rule::enum(RedoStage::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'notes.required_if' => 'Catatan wajib diisi untuk hasil QC gagal.',
        ];
    }
}
