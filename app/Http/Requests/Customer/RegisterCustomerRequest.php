<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\DTOs\Customer\RegisterCustomerDTO;
use Illuminate\Foundation\Http\FormRequest;

class RegisterCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Anyone can register
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
            'username' => ['nullable', 'string', 'max:255', 'unique:customers,username'],
            'phone' => ['required', 'string', 'max:20', 'unique:customers,phone'],
            'address' => ['nullable', 'string'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'dropPointId' => ['required', 'uuid', 'exists:drop_points,id'],
            'schoolClass' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get the DTO from the request.
     */
    public function toDTO(): RegisterCustomerDTO
    {
        return new RegisterCustomerDTO(
            name: $this->validated('name'),
            username: $this->validated('username'),
            phone: $this->validated('phone'),
            address: $this->validated('address'),
            email: $this->validated('email'),
            password: $this->validated('password'),
            drop_point_id: $this->validated('dropPointId'),
            school_class: $this->validated('schoolClass'),
        );
    }
}
