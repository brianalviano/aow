<?php

declare(strict_types=1);

namespace App\DTOs\Discount;

use App\Http\Requests\Discount\StoreDiscountRequest;
use App\Http\Requests\Discount\UpdateDiscountRequest;

final class DiscountData
{
    public function __construct(
        public string $name,
        public string $type,
        public string $scope,
        public ?string $valueType,
        public ?string $value,
        public string $startAt,
        public string $endAt,
        public bool $isActive,
        /** @var DiscountItemData[] */
        public array $items = [],
    ) {}

    public static function fromStoreRequest(StoreDiscountRequest $request): self
    {
        $v = $request->validated();
        $items = array_map(
            fn(array $row) => DiscountItemData::fromArray($row),
            (array) ($v['items'] ?? []),
        );
        return new self(
            name: (string) $v['name'],
            type: (string) $v['type'],
            scope: (string) $v['scope'],
            valueType: $v['value_type'] ?? null,
            value: isset($v['value']) ? (string) $v['value'] : null,
            startAt: (string) $v['start_at'],
            endAt: (string) $v['end_at'],
            isActive: (bool) $v['is_active'],
            items: $items,
        );
    }

    public static function fromUpdateRequest(UpdateDiscountRequest $request): self
    {
        $v = $request->validated();
        $items = array_map(
            fn(array $row) => DiscountItemData::fromArray($row),
            (array) ($v['items'] ?? []),
        );
        return new self(
            name: (string) $v['name'],
            type: (string) $v['type'],
            scope: (string) $v['scope'],
            valueType: $v['value_type'] ?? null,
            value: isset($v['value']) ? (string) $v['value'] : null,
            startAt: (string) $v['start_at'],
            endAt: (string) $v['end_at'],
            isActive: (bool) $v['is_active'],
            items: $items,
        );
    }
}
