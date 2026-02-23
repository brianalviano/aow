<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\DTOs\Customer\LoginCustomerDTO;
use Illuminate\Foundation\Http\FormRequest;

class LoginCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Anyone can login
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get the DTO from the request.
     */
    public function toDTO(): LoginCustomerDTO
    {
        return new LoginCustomerDTO(
            login: $this->validated('login'),
            password: $this->validated('password'),
            remember: (bool) $this->validated('remember', false),
        );
    }
}
