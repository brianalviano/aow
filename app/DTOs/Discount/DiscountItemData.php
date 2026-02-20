<?php

declare(strict_types=1);

namespace App\DTOs\Discount;

final class DiscountItemData
{
    public function __construct(
        public string $itemableType,
        public string $itemableId,
        public int $minQtyBuy,
        public bool $isMultiple,
        public ?string $freeProductId,
        public int $freeQtyGet,
        public ?string $customValue,
    ) {}

    /**
     * @param array<string, mixed> $row
     * @return self
     */
    public static function fromArray(array $row): self
    {
        return new self(
            itemableType: (string) ($row['itemable_type'] ?? ''),
            itemableId: (string) ($row['itemable_id'] ?? ''),
            minQtyBuy: (int) ($row['min_qty_buy'] ?? 1),
            isMultiple: (bool) (
                isset($row['is_multiple'])
                    ? (is_bool($row['is_multiple'])
                        ? $row['is_multiple']
                        : (string) $row['is_multiple'] === '1' || (string) $row['is_multiple'] === 'true')
                    : false
            ),
            freeProductId: array_key_exists('free_product_id', $row) && $row['free_product_id'] !== null && $row['free_product_id'] !== ''
                ? (string) $row['free_product_id']
                : null,
            freeQtyGet: (int) ($row['free_qty_get'] ?? 0),
            customValue: array_key_exists('custom_value', $row) && $row['custom_value'] !== null && $row['custom_value'] !== ''
                ? (string) $row['custom_value']
                : null,
        );
    }
}
