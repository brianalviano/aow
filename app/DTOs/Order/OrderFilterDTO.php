<?php

declare(strict_types=1);

namespace App\DTOs\Order;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;

/**
 * Data Transfer Object for filtering customer/admin orders.
 *
 * @property string|null $search Search query for order number, customer name, etc.
 * @property string|null $dateRange Date range filter (all, 30_days, 90_days, custom).
 * @property string|null $startDate Custom start date for filtering.
 * @property string|null $endDate Custom end date for filtering.
 * @property string|null $status Order status filter.
 * @property string|null $dropPointId Filter by drop point ID.
 * @property string|null $deliveryDate Filter by exact delivery date (YYYY-MM-DD).
 */
#[MapInputName(SnakeCaseMapper::class)]
class OrderFilterDTO extends Data
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $dateRange = 'all',
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly ?string $status = 'all',
        public readonly ?string $dropPointId = null,
        public readonly ?string $deliveryDate = null,
    ) {}
}
