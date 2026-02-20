<?php

declare(strict_types=1);

namespace App\DTOs\Sales;

class SalesItemData
{
    public function __construct(
        public string $productId,
        public int $quantity,
        public int $price,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $i): self
    {
        return new self(
            productId: (string) $i['product_id'],
            quantity: (int) $i['quantity'],
            price: (int) $i['price'],
            notes: array_key_exists('notes', $i) && $i['notes'] !== null && $i['notes'] !== '' ? (string) $i['notes'] : null,
        );
    }
}
