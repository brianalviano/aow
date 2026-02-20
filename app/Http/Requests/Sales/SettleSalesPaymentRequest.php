<?php
declare(strict_types=1);

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class SettleSalesPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('pos-operate');
    }

    public function rules(): array
    {
        return [
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.amount' => ['required', 'integer', 'min:0'],
            'payments.*.payment_method_id' => ['required', 'uuid', 'exists:payment_methods,id'],
            'payments.*.cash_bank_account_id' => ['nullable', 'uuid', 'exists:cash_bank_accounts,id'],
            'payments.*.reference_number' => ['nullable', 'string'],
            'payments.*.notes' => ['nullable', 'string'],
        ];
    }
}
