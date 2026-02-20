<?php

declare(strict_types=1);

namespace App\DTOs\Sales;

class SalesPaymentData
{
    public function __construct(
        public int $amount,
        public string $paymentMethodId,
        public ?string $cashBankAccountId = null,
        public ?string $referenceNumber = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(?array $p): ?self
    {
        if ($p === null) {
            return null;
        }
        $amount = array_key_exists('amount', $p) && $p['amount'] !== null && $p['amount'] !== '' ? (int) $p['amount'] : 0;
        if ($amount <= 0) {
            return null;
        }
        return new self(
            amount: $amount,
            paymentMethodId: (string) $p['payment_method_id'],
            cashBankAccountId: array_key_exists('cash_bank_account_id', $p) && $p['cash_bank_account_id'] !== null && $p['cash_bank_account_id'] !== '' ? (string) $p['cash_bank_account_id'] : null,
            referenceNumber: array_key_exists('reference_number', $p) && $p['reference_number'] !== null && $p['reference_number'] !== '' ? (string) $p['reference_number'] : null,
            notes: array_key_exists('notes', $p) && $p['notes'] !== null && $p['notes'] !== '' ? (string) $p['notes'] : null,
        );
    }
}
