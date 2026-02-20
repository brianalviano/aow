<?php

declare(strict_types=1);

namespace App\Http\Requests\Employee;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class UpdateMyProfileRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = (string) $this->user()->getAuthIdentifier();

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
            'phone_number' => [
                'required',
                'string',
                'max:30',
                Rule::unique('users', 'phone_number')->ignore($userId, 'id'),
            ],
            'address' => ['required', 'string', 'max:1000'],
            'birth_date' => ['required', 'date'],
            'gender' => ['required', Rule::in(['male', 'female'])],
        ];
    }
}
