<?php

declare(strict_types=1);

namespace App\DTOs\Sales;

use App\Http\Requests\Sales\{StoreSalesRequest, UpdateSalesRequest};

/**
 * Data transfer object untuk pembuatan transaksi penjualan (POS).
 *
 * Menangkap seluruh data input yang diperlukan untuk membuat Sales, termasuk item,
 * diskon, pajak, dan pembayaran awal. DTO ini tidak tergantung pada HTTP layer
 * di luar metode pembantu fromStoreRequest.
 *
 * @author PJD
 */
final class SalesData
{
    /**
     * @param array<int, SalesItemData> $items
     */
    public function __construct(
        public ?string $warehouseId,
        public ?string $cashierSessionId,
        public string $saleDatetime,
        public ?string $customerId,
        public ?string $deliveryType,
        public bool $requiresDelivery,
        public ?string $shippingRecipientName,
        public ?string $shippingRecipientPhone,
        public ?string $shippingAddress,
        public ?string $shippingNote,
        public int $subtotal,
        public ?string $discountPercentage,
        public int $itemDiscountTotal,
        public int $extraDiscountTotal,
        public int $totalAfterDiscount,
        public bool $isValueAddedTaxEnabled,
        public ?string $valueAddedTaxId,
        public ?string $valueAddedTaxPercentage,
        public int $valueAddedTaxAmount,
        public ?string $voucherCode,
        public ?int $voucherAmount,
        public ?int $shippingAmount,
        public ?int $roundingAmount,
        public int $grandTotal,
        public int $changeAmount,
        public ?string $notes,
        public string $createdById,
        public array $items,
        public array $payments,
    ) {}

    /**
     * Buat DTO dari FormRequest penyimpanan POS.
     *
     * @param StoreSalesRequest $request
     * @param string $createdById
     * @return self
     */
    public static function fromStoreRequest(StoreSalesRequest $request, string $createdById): self
    {
        $p = $request->validated();
        $payments = [];
        if (array_key_exists('payments', $p) && is_array($p['payments'])) {
            foreach ((array) $p['payments'] as $row) {
                $dto = SalesPaymentData::fromArray($row);
                if ($dto !== null) {
                    $payments[] = $dto;
                }
            }
        } else {
            $single = array_key_exists('payment', $p) ? SalesPaymentData::fromArray($p['payment']) : null;
            if ($single !== null) {
                $payments[] = $single;
            }
        }
        return new self(
            warehouseId: (array_key_exists('warehouse_id', $p) && $p['warehouse_id']) ? (string) $p['warehouse_id'] : null,
            cashierSessionId: array_key_exists('cashier_session_id', $p) && $p['cashier_session_id'] ? (string) $p['cashier_session_id'] : null,
            saleDatetime: (string) $p['sale_datetime'],
            customerId: array_key_exists('customer_id', $p) && $p['customer_id'] ? (string) $p['customer_id'] : null,
            deliveryType: array_key_exists('delivery_type', $p) && $p['delivery_type'] ? (string) $p['delivery_type'] : null,
            requiresDelivery: (bool) ($p['requires_delivery'] ?? false),
            shippingRecipientName: array_key_exists('shipping_recipient_name', $p) && $p['shipping_recipient_name'] ? (string) $p['shipping_recipient_name'] : null,
            shippingRecipientPhone: array_key_exists('shipping_recipient_phone', $p) && $p['shipping_recipient_phone'] ? (string) $p['shipping_recipient_phone'] : null,
            shippingAddress: array_key_exists('shipping_address', $p) && $p['shipping_address'] ? (string) $p['shipping_address'] : null,
            shippingNote: array_key_exists('shipping_note', $p) && $p['shipping_note'] ? (string) $p['shipping_note'] : null,
            subtotal: (int) ($p['subtotal'] ?? 0),
            discountPercentage: array_key_exists('discount_percentage', $p) && $p['discount_percentage'] !== null ? (string) $p['discount_percentage'] : null,
            itemDiscountTotal: (int) ($p['item_discount_total'] ?? 0),
            extraDiscountTotal: (int) ($p['extra_discount_total'] ?? 0),
            totalAfterDiscount: (int) ($p['total_after_discount'] ?? 0),
            isValueAddedTaxEnabled: (bool) ($p['is_value_added_tax_enabled'] ?? false),
            valueAddedTaxId: array_key_exists('value_added_tax_id', $p) && $p['value_added_tax_id'] ? (string) $p['value_added_tax_id'] : null,
            valueAddedTaxPercentage: array_key_exists('value_added_tax_percentage', $p) && $p['value_added_tax_percentage'] !== null ? (string) $p['value_added_tax_percentage'] : null,
            valueAddedTaxAmount: (int) ($p['value_added_tax_amount'] ?? 0),
            voucherCode: array_key_exists('voucher_code', $p) && $p['voucher_code'] ? (string) $p['voucher_code'] : null,
            voucherAmount: array_key_exists('voucher_amount', $p) && $p['voucher_amount'] !== null ? (int) $p['voucher_amount'] : null,
            shippingAmount: array_key_exists('shipping_amount', $p) && $p['shipping_amount'] !== null ? (int) $p['shipping_amount'] : null,
            roundingAmount: array_key_exists('rounding_amount', $p) && $p['rounding_amount'] !== null ? (int) $p['rounding_amount'] : null,
            grandTotal: (int) ($p['grand_total'] ?? 0),
            changeAmount: (int) ($p['change_amount'] ?? 0),
            notes: array_key_exists('notes', $p) && $p['notes'] ? (string) $p['notes'] : null,
            createdById: $createdById,
            items: array_map(fn($i) => SalesItemData::fromArray($i), (array) ($p['items'] ?? [])),
            payments: $payments,
        );
    }

    /**
     * Buat DTO dari FormRequest pembaruan POS.
     *
     * @param UpdateSalesRequest $request
     * @param string $createdById
     * @return self
     */
    public static function fromUpdateRequest(UpdateSalesRequest $request, string $createdById): self
    {
        $p = $request->validated();
        $payments = [];
        if (array_key_exists('payments', $p) && is_array($p['payments'])) {
            foreach ((array) $p['payments'] as $row) {
                $dto = SalesPaymentData::fromArray($row);
                if ($dto !== null) {
                    $payments[] = $dto;
                }
            }
        } else {
            $single = array_key_exists('payment', $p) ? SalesPaymentData::fromArray($p['payment']) : null;
            if ($single !== null) {
                $payments[] = $single;
            }
        }
        return new self(
            warehouseId: array_key_exists('warehouse_id', $p) && $p['warehouse_id'] ? (string) $p['warehouse_id'] : null,
            cashierSessionId: array_key_exists('cashier_session_id', $p) && $p['cashier_session_id'] ? (string) $p['cashier_session_id'] : null,
            saleDatetime: array_key_exists('sale_datetime', $p) && $p['sale_datetime'] ? (string) $p['sale_datetime'] : now()->toDateTimeString(),
            customerId: array_key_exists('customer_id', $p) && $p['customer_id'] ? (string) $p['customer_id'] : null,
            deliveryType: array_key_exists('delivery_type', $p) && $p['delivery_type'] ? (string) $p['delivery_type'] : null,
            requiresDelivery: (bool) ($p['requires_delivery'] ?? false),
            shippingRecipientName: array_key_exists('shipping_recipient_name', $p) && $p['shipping_recipient_name'] ? (string) $p['shipping_recipient_name'] : null,
            shippingRecipientPhone: array_key_exists('shipping_recipient_phone', $p) && $p['shipping_recipient_phone'] ? (string) $p['shipping_recipient_phone'] : null,
            shippingAddress: array_key_exists('shipping_address', $p) && $p['shipping_address'] ? (string) $p['shipping_address'] : null,
            shippingNote: array_key_exists('shipping_note', $p) && $p['shipping_note'] ? (string) $p['shipping_note'] : null,
            subtotal: (int) ($p['subtotal'] ?? 0),
            discountPercentage: array_key_exists('discount_percentage', $p) && $p['discount_percentage'] !== null ? (string) $p['discount_percentage'] : null,
            itemDiscountTotal: (int) ($p['item_discount_total'] ?? 0),
            extraDiscountTotal: (int) ($p['extra_discount_total'] ?? 0),
            totalAfterDiscount: (int) ($p['total_after_discount'] ?? 0),
            isValueAddedTaxEnabled: (bool) ($p['is_value_added_tax_enabled'] ?? false),
            valueAddedTaxId: array_key_exists('value_added_tax_id', $p) && $p['value_added_tax_id'] ? (string) $p['value_added_tax_id'] : null,
            valueAddedTaxPercentage: array_key_exists('value_added_tax_percentage', $p) && $p['value_added_tax_percentage'] !== null ? (string) $p['value_added_tax_percentage'] : null,
            valueAddedTaxAmount: (int) ($p['value_added_tax_amount'] ?? 0),
            voucherCode: array_key_exists('voucher_code', $p) && $p['voucher_code'] ? (string) $p['voucher_code'] : null,
            voucherAmount: array_key_exists('voucher_amount', $p) && $p['voucher_amount'] !== null ? (int) $p['voucher_amount'] : null,
            shippingAmount: array_key_exists('shipping_amount', $p) && $p['shipping_amount'] !== null ? (int) $p['shipping_amount'] : null,
            roundingAmount: array_key_exists('rounding_amount', $p) && $p['rounding_amount'] !== null ? (int) $p['rounding_amount'] : null,
            grandTotal: (int) ($p['grand_total'] ?? 0),
            changeAmount: (int) ($p['change_amount'] ?? 0),
            notes: array_key_exists('notes', $p) && $p['notes'] ? (string) $p['notes'] : null,
            createdById: $createdById,
            items: array_map(fn($i) => SalesItemData::fromArray($i), (array) ($p['items'] ?? [])),
            payments: $payments,
        );
    }

    public static function fromArray(array $p, string $createdById): self
    {
        $payments = [];
        if (array_key_exists('payments', $p) && is_array($p['payments'])) {
            foreach ((array) $p['payments'] as $row) {
                $dto = SalesPaymentData::fromArray(is_array($row) ? $row : null);
                if ($dto !== null) {
                    $payments[] = $dto;
                }
            }
        } else {
            $single = array_key_exists('payment', $p) ? SalesPaymentData::fromArray(is_array($p['payment'] ?? null) ? $p['payment'] : null) : null;
            if ($single !== null) {
                $payments[] = $single;
            }
        }
        return new self(
            warehouseId: array_key_exists('warehouse_id', $p) && $p['warehouse_id'] ? (string) $p['warehouse_id'] : null,
            cashierSessionId: array_key_exists('cashier_session_id', $p) && $p['cashier_session_id'] ? (string) $p['cashier_session_id'] : null,
            saleDatetime: (string) ($p['sale_datetime'] ?? now()->toDateTimeString()),
            customerId: array_key_exists('customer_id', $p) && $p['customer_id'] ? (string) $p['customer_id'] : null,
            deliveryType: array_key_exists('delivery_type', $p) && $p['delivery_type'] ? (string) $p['delivery_type'] : null,
            requiresDelivery: (bool) ($p['requires_delivery'] ?? false),
            shippingRecipientName: array_key_exists('shipping_recipient_name', $p) && $p['shipping_recipient_name'] ? (string) $p['shipping_recipient_name'] : null,
            shippingRecipientPhone: array_key_exists('shipping_recipient_phone', $p) && $p['shipping_recipient_phone'] ? (string) $p['shipping_recipient_phone'] : null,
            shippingAddress: array_key_exists('shipping_address', $p) && $p['shipping_address'] ? (string) $p['shipping_address'] : null,
            shippingNote: array_key_exists('shipping_note', $p) && $p['shipping_note'] ? (string) $p['shipping_note'] : null,
            subtotal: (int) ($p['subtotal'] ?? 0),
            discountPercentage: array_key_exists('discount_percentage', $p) && $p['discount_percentage'] !== null ? (string) $p['discount_percentage'] : null,
            itemDiscountTotal: (int) ($p['item_discount_total'] ?? 0),
            extraDiscountTotal: (int) ($p['extra_discount_total'] ?? 0),
            totalAfterDiscount: (int) ($p['total_after_discount'] ?? 0),
            isValueAddedTaxEnabled: (bool) ($p['is_value_added_tax_enabled'] ?? false),
            valueAddedTaxId: array_key_exists('value_added_tax_id', $p) && $p['value_added_tax_id'] ? (string) $p['value_added_tax_id'] : null,
            valueAddedTaxPercentage: array_key_exists('value_added_tax_percentage', $p) && $p['value_added_tax_percentage'] !== null ? (string) $p['value_added_tax_percentage'] : null,
            valueAddedTaxAmount: (int) ($p['value_added_tax_amount'] ?? 0),
            voucherCode: array_key_exists('voucher_code', $p) && $p['voucher_code'] ? (string) $p['voucher_code'] : null,
            voucherAmount: array_key_exists('voucher_amount', $p) && $p['voucher_amount'] !== null ? (int) $p['voucher_amount'] : null,
            shippingAmount: array_key_exists('shipping_amount', $p) && $p['shipping_amount'] !== null ? (int) $p['shipping_amount'] : null,
            roundingAmount: array_key_exists('rounding_amount', $p) && $p['rounding_amount'] !== null ? (int) $p['rounding_amount'] : null,
            grandTotal: (int) ($p['grand_total'] ?? 0),
            changeAmount: (int) ($p['change_amount'] ?? 0),
            notes: array_key_exists('notes', $p) && $p['notes'] ? (string) $p['notes'] : null,
            createdById: $createdById,
            items: array_map(fn($i) => SalesItemData::fromArray(is_array($i) ? $i : []), (array) ($p['items'] ?? [])),
            payments: $payments,
        );
    }
}
