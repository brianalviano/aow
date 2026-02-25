<?php

declare(strict_types=1);

namespace App\DTOs\Order;

/**
 * Data Transfer Object for filtering customer orders.
 */
readonly class OrderFilterDTO
{
    public function __construct(
        public ?string $search = null,
        public ?string $dateRange = 'all', // all, 30_days, 90_days, custom
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?string $status = 'all',
    ) {}

    /**
     * Create a DTO from request parameters.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            dateRange: $data['date_range'] ?? 'all',
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
            status: $data['status'] ?? 'all',
        );
    }
}
