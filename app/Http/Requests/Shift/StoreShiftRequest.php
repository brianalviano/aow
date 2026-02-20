<?php

declare(strict_types=1);

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_time' => ['nullable', 'date_format:H:i', 'required_unless:is_off,true'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_unless:is_off,true'],
            'is_overnight' => ['required', 'boolean'],
            'is_off' => ['required', 'boolean'],
            'color' => ['nullable', 'string', Rule::in([
                'indigo',
                'purple',
                'blue',
                'green',
                'red',
                'orange',
                'pink',
                'teal',
                'cyan',
                'yellow',
            ])],
        ];
    }
}
