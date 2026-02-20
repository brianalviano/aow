<?php

declare(strict_types=1);

namespace App\DTOs\Voucher;

final class VoucherData
{
    public function __construct(
        public string $code,
        public string $name,
        public ?string $description,
        public string $valueType,
        public ?string $value,
        public ?string $startAt,
        public ?string $endAt,
        public bool $isActive,
        public int $usageLimit,
        public int $maxUsesPerCustomer,
        public ?string $minOrderAmount,
    ) {}
}
