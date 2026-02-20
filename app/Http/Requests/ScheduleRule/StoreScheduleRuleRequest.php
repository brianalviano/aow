<?php

declare(strict_types=1);

namespace App\Http\Requests\ScheduleRule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('admin-only');
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'uuid',
                Rule::unique('schedule_rules', 'user_id'),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['sometimes', 'boolean'],
            'rotation_even_shift_id' => ['nullable', 'uuid', 'required_without:details'],
            'rotation_odd_shift_id' => ['nullable', 'uuid', 'required_without:details'],
            'rotation_off_day' => ['nullable', 'integer', 'between:0,6'],
            'details' => ['nullable', 'array', 'size:7', 'required_without_all:rotation_even_shift_id,rotation_odd_shift_id'],
            'details.*.day_of_week' => ['required_with:details', 'integer', 'between:0,6'],
            'details.*.shift_id' => ['nullable', 'uuid'],
        ];
    }
}
