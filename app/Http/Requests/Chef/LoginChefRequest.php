<?php

declare(strict_types=1);

namespace App\Http\Requests\Chef;

use App\DTOs\Chef\LoginChefDTO;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Handle chef login validation.
 */
class LoginChefRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Anyone can attempt to login
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
    public function toDTO(): LoginChefDTO
    {
        return new LoginChefDTO(
            login: (string) $this->validated('login'),
            password: (string) $this->validated('password'),
            remember: (bool) $this->validated('remember', false),
        );
    }
}
