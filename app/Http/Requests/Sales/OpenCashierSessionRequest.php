<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales;

use App\Http\Requests\BaseApiRequest;

final class OpenCashierSessionRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('pos-operate');
    }

    public function rules(): array
    {
        return [
            'opening_balance' => ['required', 'integer', 'min:0'],
        ];
    }
}
