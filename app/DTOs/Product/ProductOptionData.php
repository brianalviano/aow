<?php

declare(strict_types=1);

namespace App\DTOs\Product;

/**
 * Data Transfer Object for Product Option.
 */
class ProductOptionData
{
    /**
     * @param string $name
     * @param bool $isRequired
     * @param int $sortOrder
     * @param array<int, ProductOptionItemData> $items
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $isRequired = false,
        public readonly bool $isMultiple = false,
        public readonly int $sortOrder = 0,
        public readonly array $items = [],
    ) {}

    /**
     * Create DTO from array data.
     *
     * @param array<string, mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemData) {
                $items[] = ProductOptionItemData::fromArray($itemData);
            }
        }

        return new self(
            name: (string) ($data['name'] ?? ''),
            isRequired: (bool) ($data['is_required'] ?? false),
            isMultiple: (bool) ($data['is_multiple'] ?? false),
            sortOrder: (int) ($data['sort_order'] ?? 0),
            items: $items,
        );
    }
}
