<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\PickUpPoint;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePickUpPointRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'officer_ids' => ['nullable', 'array'],
            'officer_ids.*' => ['exists:pick_up_point_officers,id'],
        ];
    }
}
