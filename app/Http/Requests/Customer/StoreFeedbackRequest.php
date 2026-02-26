<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request for storing customer feedback.
 */
class StoreFeedbackRequest extends FormRequest
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
            'type'    => ['required', 'string', 'in:kritik,saran,lainnya'],
            'content' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in'      => 'Tipe feedback harus berupa kritik, saran, atau lainnya.',
            'content.min'  => 'Konten feedback minimal 10 karakter.',
            'content.max'  => 'Konten feedback maksimal 2000 karakter.',
        ];
    }
}
