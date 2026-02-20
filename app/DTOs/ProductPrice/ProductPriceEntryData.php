<?php

declare(strict_types=1);

namespace App\DTOs\ProductPrice;

/**
 * DTO untuk satu perubahan harga.
 */
class ProductPriceEntryData
{
    public function __construct(
        public string $type,
        public string $productId,
        public ?string $levelId,
        public ?string $supplierId,
        public ?int $price,
    ) {}

    /**
     * Buat DTO dari array.
     *
     * @param array $entry
     * @return self
     */
    public static function fromArray(array $entry): self
    {
        return new self(
            type: (string) $entry['type'],
            productId: (string) $entry['product_id'],
            levelId: array_key_exists('selling_price_level_id', $entry) && $entry['selling_price_level_id'] !== null && $entry['selling_price_level_id'] !== ''
                ? (string) $entry['selling_price_level_id']
                : null,
            supplierId: array_key_exists('supplier_id', $entry) && $entry['supplier_id'] !== null && $entry['supplier_id'] !== ''
                ? (string) $entry['supplier_id']
                : null,
            price: array_key_exists('price', $entry) && $entry['price'] !== null && $entry['price'] !== ''
                ? (int) $entry['price']
                : null,
        );
    }
}
