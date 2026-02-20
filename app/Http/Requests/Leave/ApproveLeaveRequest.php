<?php

namespace App\Http\Requests\Leave;

use App\Enums\LeaveRequestStatus;
use Illuminate\Foundation\Http\FormRequest;

class ApproveLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:' . implode(',', [LeaveRequestStatus::Approved->value, LeaveRequestStatus::Rejected->value])],
        ];
    }
}
