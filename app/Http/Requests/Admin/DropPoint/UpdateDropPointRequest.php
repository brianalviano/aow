<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\DropPoint;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDropPointRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', new \Illuminate\Validation\Rules\Enum(\App\Enums\DropPointCategory::class)],
            'photo' => ['nullable', 'image', 'max:2048'],
            'address' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'pic_phone' => ['nullable', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'delivery_fee' => ['required', 'integer', 'min:0'],
            'min_po_qty' => ['nullable', 'integer', 'min:0'],
            'min_po_amount' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
