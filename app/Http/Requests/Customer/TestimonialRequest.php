<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class TestimonialRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('customer')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating'  => ['required', 'string', 'in:1,2,3,4,5'],
            'content' => ['nullable', 'string', 'max:1000'],
            'photo'   => ['nullable', 'image', 'max:2048'], // Max 2MB
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'rating.required' => 'Rating wajib diisi.',
            'rating.in'       => 'Rating tidak valid.',
            'content.max'     => 'Konten testimoni maksimal 1000 karakter.',
            'photo.image'     => 'Format foto harus berupa gambar.',
            'photo.max'       => 'Ukuran foto maksimal 2MB.',
        ];
    }
}
