<?php

declare(strict_types=1);

namespace App\DTOs\PurchaseOrder;

use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\UpdatePurchaseOrderRequest;

class PurchaseOrderData
{
    /**
     * @param array<PurchaseOrderItemData> $items
     */
    public function __construct(
        public ?string $supplierId,
        public string $warehouseId,
        public string $orderDate,
        public ?string $dueDate,
        public ?string $notes,
        public ?string $status,
        public ?int $deliveryCost,
        public ?string $discountPercentage,
        public bool $isValueAddedTaxEnabled,
        public bool $valueAddedTaxIncluded,
        public ?string $valueAddedTaxId,
        public ?string $valueAddedTaxPercentage,
        public bool $isIncomeTaxEnabled,
        public ?string $incomeTaxId,
        public ?string $incomeTaxPercentage,
        public ?string $supplierInvoiceNumber,
        public ?string $supplierInvoiceFile,
        public ?string $supplierInvoiceDate,
        public string $createdById,
        public array $items,
    ) {}

    public static function fromStoreRequest(StorePurchaseOrderRequest $request, string $createdById): self
    {
        $p = $request->validated();
        return new self(
            supplierId: $p['supplier_id'] ?? null,
            warehouseId: (string) $p['warehouse_id'],
            orderDate: (string) $p['order_date'],
            dueDate: $p['due_date'] ?? null,
            notes: $p['notes'] ?? null,
            status: $p['status'] ?? null,
            deliveryCost: isset($p['delivery_cost']) ? (int) $p['delivery_cost'] : null,
            discountPercentage: isset($p['discount_percentage']) ? (string) $p['discount_percentage'] : null,
            isValueAddedTaxEnabled: (bool) ($p['is_value_added_tax_enabled'] ?? false),
            valueAddedTaxIncluded: (bool) ($p['value_added_tax_included'] ?? false),
            valueAddedTaxId: $p['value_added_tax_id'] ?? null,
            valueAddedTaxPercentage: $p['value_added_tax_percentage'] ?? null,
            isIncomeTaxEnabled: (bool) ($p['is_income_tax_enabled'] ?? false),
            incomeTaxId: $p['income_tax_id'] ?? null,
            incomeTaxPercentage: $p['income_tax_percentage'] ?? null,
            supplierInvoiceNumber: $p['supplier_invoice_number'] ?? null,
            supplierInvoiceFile: $p['supplier_invoice_file'] ?? null,
            supplierInvoiceDate: $p['supplier_invoice_date'] ?? null,
            createdById: $createdById,
            items: array_map(function ($i) {
                return new PurchaseOrderItemData(
                    purchaseRequestItemId: $i['purchase_request_item_id'] ?? null,
                    productId: (string) $i['product_id'],
                    quantity: (int) $i['quantity'],
                    price: (int) $i['price'],
                    notes: $i['notes'] ?? null,
                );
            }, $p['items'] ?? []),
        );
    }

    public static function fromUpdateRequest(UpdatePurchaseOrderRequest $request, string $createdById): self
    {
        $p = $request->validated();
        return new self(
            supplierId: $p['supplier_id'] ?? null,
            warehouseId: (string) $p['warehouse_id'],
            orderDate: (string) $p['order_date'],
            dueDate: $p['due_date'] ?? null,
            notes: $p['notes'] ?? null,
            status: $p['status'] ?? null,
            deliveryCost: isset($p['delivery_cost']) ? (int) $p['delivery_cost'] : null,
            discountPercentage: isset($p['discount_percentage']) ? (string) $p['discount_percentage'] : null,
            isValueAddedTaxEnabled: (bool) ($p['is_value_added_tax_enabled'] ?? false),
            valueAddedTaxIncluded: (bool) ($p['value_added_tax_included'] ?? false),
            valueAddedTaxId: $p['value_added_tax_id'] ?? null,
            valueAddedTaxPercentage: $p['value_added_tax_percentage'] ?? null,
            isIncomeTaxEnabled: (bool) ($p['is_income_tax_enabled'] ?? false),
            incomeTaxId: $p['income_tax_id'] ?? null,
            incomeTaxPercentage: $p['income_tax_percentage'] ?? null,
            supplierInvoiceNumber: $p['supplier_invoice_number'] ?? null,
            supplierInvoiceFile: $p['supplier_invoice_file'] ?? null,
            supplierInvoiceDate: $p['supplier_invoice_date'] ?? null,
            createdById: $createdById,
            items: array_map(function ($i) {
                return new PurchaseOrderItemData(
                    purchaseRequestItemId: $i['purchase_request_item_id'] ?? null,
                    productId: (string) $i['product_id'],
                    quantity: (int) $i['quantity'],
                    price: (int) $i['price'],
                    notes: $i['notes'] ?? null,
                );
            }, $p['items'] ?? []),
        );
    }
}
