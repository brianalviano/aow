<?php

declare(strict_types=1);

namespace App\Http\Requests\StockOpname;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\{StockOpnameStatus, RoleName};
use App\Models\Role;

class StoreStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleIds = Role::query()
            ->whereIn('name', RoleName::stockOpnameAssignable())
            ->pluck('id')
            ->all();

        return [
            'warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],
            'scheduled_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'uuid', 'exists:products,id'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => [
                'required',
                'uuid',
                Rule::exists('users', 'id')->where(function ($q) use ($roleIds) {
                    $q->whereIn('role_id', $roleIds);
                }),
            ],
            'status' => ['nullable', Rule::in(StockOpnameStatus::values())],
        ];
    }
}
