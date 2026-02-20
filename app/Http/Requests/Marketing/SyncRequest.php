<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketing;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;

class SyncRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = (string) ($this->user()?->getAuthIdentifier() ?? '');
        return [
            'customers_create' => ['nullable', 'array'],
            'customers_update' => ['nullable', 'array'],
            'customers_update.*.id' => ['required', 'uuid', 'exists:customers,id'],
            'sales_create' => ['nullable', 'array'],
            'sales_update' => ['nullable', 'array'],
            'sales_update.*.id' => ['required', 'uuid', 'exists:sales,id'],
            'notifications_mark_read' => ['nullable', 'array'],
            'notifications_mark_read.*' => ['string'],
            'profile_update' => ['nullable', 'array'],
            'profile_update.name' => ['sometimes', 'string', 'max:255'],
            'profile_update.email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId, 'id'),
            ],
            'profile_update.username' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($userId, 'id'),
            ],
            'profile_update.password' => ['nullable', 'string', 'min:8'],
            'profile_update.phone_number' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('users', 'phone_number')->ignore($userId, 'id'),
            ],
            'profile_update.address' => ['sometimes', 'string', 'max:1000'],
            'profile_update.birth_date' => ['sometimes', 'date'],
            'profile_update.gender' => ['sometimes', Rule::in(['male', 'female'])],
        ];
    }
}
