<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role_id' => ['nullable', 'uuid', 'exists:roles,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'entries' => ['required', 'array'],
            'entries.*.user_id' => ['required', 'uuid', 'exists:users,id'],
            'entries.*.date' => ['required', 'date_format:Y-m-d'],
            'entries.*.shift_id' => ['nullable', 'uuid', 'exists:shifts,id'],
        ];
    }
}
