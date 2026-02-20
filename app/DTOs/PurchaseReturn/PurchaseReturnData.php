<?php

declare(strict_types=1);

namespace App\DTOs\PurchaseReturn;

final class PurchaseReturnData
{
    public ?string $purchaseOrderId;
    public string $supplierId;
    public string $warehouseId;
    public ?string $number;
    public string $returnDate;
    public string $reason;
    public string $resolution;
    public string $status;
    public ?string $notes;
    public string $createdById;
    /** @var array<int, PurchaseReturnItemData> */
    public array $items;

    /**
     * @param array<int, PurchaseReturnItemData> $items
     */
    public function __construct(
        ?string $purchaseOrderId,
        string $supplierId,
        string $warehouseId,
        ?string $number,
        string $returnDate,
        string $reason,
        string $resolution,
        string $status,
        ?string $notes,
        string $createdById,
        array $items
    ) {
        $this->purchaseOrderId = $purchaseOrderId;
        $this->supplierId = $supplierId;
        $this->warehouseId = $warehouseId;
        $this->number = $number;
        $this->returnDate = $returnDate;
        $this->reason = $reason;
        $this->resolution = $resolution;
        $this->status = $status;
        $this->notes = $notes;
        $this->createdById = $createdById;
        $this->items = $items;
    }
}

