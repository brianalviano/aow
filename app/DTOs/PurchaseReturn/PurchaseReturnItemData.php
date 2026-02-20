<?php

declare(strict_types=1);

namespace App\DTOs\PurchaseReturn;

final class PurchaseReturnItemData
{
    public string $productId;
    public int $quantity;
    public int $price;
    public ?string $notes;
    public ?string $productDivisionId;
    public ?string $productRackId;

    public function __construct(
        string $productId,
        int $quantity,
        int $price = 0,
        ?string $notes = null,
        ?string $productDivisionId = null,
        ?string $productRackId = null
    ) {
        $this->productId = $productId;
        $this->quantity = $quantity;
        $this->price = $price;
        $this->notes = $notes;
        $this->productDivisionId = $productDivisionId;
        $this->productRackId = $productRackId;
    }
}
