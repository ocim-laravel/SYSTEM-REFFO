<?php

namespace App\Http\Requests\System;

use App\Models\WorkCalendar;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workCalendar = $this->route('work_calendar');

        return $workCalendar instanceof WorkCalendar
            ? (bool) $this->user()?->can('update', $workCalendar)
            : (bool) $this->user()?->can('create', WorkCalendar::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'date' => $this->filled('date')
                ? Carbon::parse($this->input('date'))->toDateString()
                : $this->input('date'),
            'is_workday' => $this->boolean('is_workday'),
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $workCalendar = $this->route('work_calendar');

        return [
            'date' => [
                'required',
                'date',
                Rule::unique('work_calendars', 'date')->ignore($workCalendar instanceof WorkCalendar ? $workCalendar->id : null),
            ],
            'is_workday' => ['boolean'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }
}
