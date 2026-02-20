<?php

declare(strict_types=1);

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Shift;

class UpdateShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeParam = $this->route('shift');
        $id = $routeParam instanceof Shift ? (string) $routeParam->getKey() : (string) $routeParam;

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
