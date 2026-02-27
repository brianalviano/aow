<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Slider;

use App\Traits\FileHelperTrait;
use Illuminate\Foundation\Http\FormRequest;

class StoreSliderRequest extends FormRequest
{
    use FileHelperTrait;

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
            'photo' => $this->getFileValidationRules(true, [
                'allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],
                'max_size' => 2 * 1024 * 1024, // 2MB
            ]),
        ];
    }
}
