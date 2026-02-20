<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales;

use App\Http\Requests\BaseApiRequest;
use Illuminate\Validation\Rule;
use App\Enums\SalesDeliveryType;

/**
 * Validasi input untuk pembuatan transaksi penjualan (POS).
 *
 * @author PJD
 */
class StoreSalesRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('pos-operate');
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['nullable', 'uuid', 'exists:warehouses,id'],
            'cashier_session_id' => ['nullable', 'uuid', 'exists:cashier_sessions,id'],
            'sale_datetime' => ['required', 'date'],
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'delivery_type' => ['nullable', Rule::in(SalesDeliveryType::values())],
            'requires_delivery' => ['nullable', 'boolean'],
            'shipping_recipient_name' => ['nullable', 'string'],
            'shipping_recipient_phone' => ['nullable', 'string'],
            'shipping_address' => ['nullable', 'string'],
            'shipping_note' => ['nullable', 'string'],
            'subtotal' => ['required', 'integer', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'item_discount_total' => ['nullable', 'integer', 'min:0'],
            'extra_discount_total' => ['nullable', 'integer', 'min:0'],
            'total_after_discount' => ['required', 'integer', 'min:0'],
            'is_value_added_tax_enabled' => ['nullable', 'boolean'],
            'value_added_tax_id' => ['nullable', 'uuid', 'exists:value_added_taxes,id'],
            'value_added_tax_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'value_added_tax_amount' => ['required', 'integer', 'min:0'],
            'grand_total' => ['required', 'integer', 'min:0'],
            'change_amount' => ['nullable', 'integer', 'min:0'],
            'shipping_amount' => ['nullable', 'integer', 'min:0'],
            'rounding_amount' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
            'payment' => ['nullable', 'array'],
            'payment.amount' => ['nullable', 'integer', 'min:0'],
            'payment.payment_method_id' => ['required_with:payment.amount', 'uuid', 'exists:payment_methods,id'],
            'payment.cash_bank_account_id' => ['nullable', 'uuid', 'exists:cash_bank_accounts,id'],
            'payment.reference_number' => ['nullable', 'string'],
            'payment.notes' => ['nullable', 'string'],
            'payments' => ['nullable', 'array', 'min:1'],
            'payments.*.amount' => ['required', 'integer', 'min:0'],
            'payments.*.payment_method_id' => ['required', 'uuid', 'exists:payment_methods,id'],
            'payments.*.cash_bank_account_id' => ['nullable', 'uuid', 'exists:cash_bank_accounts,id'],
            'payments.*.reference_number' => ['nullable', 'string'],
            'payments.*.notes' => ['nullable', 'string'],
            'voucher_code' => ['nullable', 'string', 'max:20'],
            'voucher_amount' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $grandTotal = (int) ($this->input('grand_total') ?? 0);
            $payments = $this->input('payments');
            $sumPaid = 0;
            if (is_array($payments)) {
                foreach ($payments as $p) {
                    $amt = (int) ($p['amount'] ?? 0);
                    if ($amt > 0) {
                        $sumPaid += $amt;
                    }
                }
            }
            $customerId = (string) ($this->input('customer_id') ?? '');
            if ($grandTotal > $sumPaid && $customerId === '') {
                $validator->errors()->add('customer_id', 'Customer wajib dipilih untuk transaksi piutang.');
            }
        });
    }
}
