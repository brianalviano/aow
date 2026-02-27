<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\DTOs\Customer\UpdateProfileDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileUpdateRequest extends FormRequest
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
        $customerId = auth('customer')->id();

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('customers', 'username')->ignore($customerId)],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customerId)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('customers', 'phone')->ignore($customerId)],
            'schoolClass' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Get the DTO from the request.
     */
    public function toDTO(): UpdateProfileDTO
    {
        return new UpdateProfileDTO(
            name: $this->validated('name'),
            phone: $this->validated('phone'),
            username: $this->validated('username'),
            email: $this->validated('email'),
            password: $this->validated('password'),
            school_class: $this->validated('schoolClass'),
        );
    }
}
