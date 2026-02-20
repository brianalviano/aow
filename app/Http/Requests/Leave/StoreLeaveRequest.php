<?php

namespace App\Http\Requests\Leave;

use App\Enums\LeaveRequestType;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'type' => ['required', 'in:' . implode(',', LeaveRequestType::values())],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
