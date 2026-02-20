<?php

declare(strict_types=1);

namespace App\Http\Requests\Customer;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;
use App\Models\Customer;

class UpdateCustomerRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $routeParam = $this->route('customer');
        $customerId = $routeParam instanceof Customer ? (string) $routeParam->getKey() : (string) $routeParam;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customerId, 'id'),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['nullable', 'boolean'],
            'is_visible_in_pos' => ['nullable', 'boolean'],
            'is_visible_in_marketing' => ['nullable', 'boolean'],
            'marketer_ids' => ['nullable', 'array'],
            'marketer_ids.*' => ['uuid', 'exists:users,id'],
        ];
    }
}
