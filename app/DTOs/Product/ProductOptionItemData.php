<?php

declare(strict_types=1);

namespace App\DTOs\Product;

/**
 * Data Transfer Object for Product Option Item.
 */
class ProductOptionItemData
{
    public function __construct(
        public readonly string $name,
        public readonly int $extraPrice = 0,
        public readonly int $sortOrder = 0,
    ) {}

    /**
     * Create DTO from array data.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            extraPrice: (int) ($data['extra_price'] ?? 0),
            sortOrder: (int) ($data['sort_order'] ?? 0),
        );
    }
}
