<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\PickUpPointOfficer;

use Illuminate\Foundation\Http\FormRequest;

class StorePickUpPointOfficerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:pick_up_point_officers,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'pick_up_point_id' => ['nullable', 'exists:pick_up_points,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
