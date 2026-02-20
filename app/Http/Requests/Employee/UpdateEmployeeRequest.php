<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeParam = $this->route('employee');
        $userId = $routeParam instanceof User ? (string) $routeParam->getKey() : (string) $routeParam;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId, 'id'),
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($userId, 'id'),
            ],
            'password' => ['nullable', 'string', 'min:8'],
            'pin' => ['nullable', 'string', 'min:6'],
            'role_id' => ['nullable', 'uuid', 'exists:roles,id'],
            'join_date' => ['nullable', 'date'],
            'phone_number' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'phone_number')->ignore($userId, 'id'),
            ],
            'address' => ['nullable', 'string', 'max:1000'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
