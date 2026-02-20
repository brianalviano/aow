<?php
declare(strict_types=1);

namespace App\DTOs\PurchaseOrder;

class PurchaseOrderItemData
{
    public function __construct(
        public ?string $purchaseRequestItemId,
        public string $productId,
        public int $quantity,
        public int $price,
        public ?string $notes,
    ) {}
}

