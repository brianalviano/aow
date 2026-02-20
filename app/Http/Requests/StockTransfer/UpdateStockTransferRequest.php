<?php

declare(strict_types=1);

namespace App\Http\Requests\StockTransfer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\StockTransferStatus;

/**
 * Validasi pembaruan Stock Transfer.
 *
 * @author PJD
 *
 * @throws \Illuminate\Validation\ValidationException
 */
class UpdateStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'uuid', 'exists:warehouses,id'],
            'to_owner_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(StockTransferStatus::values())],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.from_owner_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'items.*.to_owner_user_id' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}

