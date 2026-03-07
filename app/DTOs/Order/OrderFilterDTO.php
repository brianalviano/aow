<?php

declare(strict_types=1);

namespace App\DTOs\Order;

use Spatie\LaravelData\Data;

/**
 * Data Transfer Object for filtering customer/chef/admin orders.
 *
 * @property string|null $search Search query for order number, customer name, etc.
 * @property string|null $dateRange Date range filter (all, 30_days, 90_days, custom).
 * @property string|null $startDate Custom start date for filtering.
 * @property string|null $endDate Custom end date for filtering.
 * @property string|null $status Order status filter.
 */
class OrderFilterDTO extends Data
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?string $dateRange = 'all',
        public readonly ?string $startDate = null,
        public readonly ?string $endDate = null,
        public readonly ?string $status = 'all',
    ) {}
}
