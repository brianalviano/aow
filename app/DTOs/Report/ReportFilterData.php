<?php

declare(strict_types=1);

namespace App\DTOs\Report;

use Spatie\LaravelData\{Attributes\Validation\Rule, Data};

/**
 * DTO for report filter parameters.
 *
 * Used across report index, PDF export, and Excel export actions.
 */
class ReportFilterData extends Data
{
    public function __construct(
        #[Rule(['nullable', 'date'])]
        public readonly ?string $dateFrom = null,

        #[Rule(['nullable', 'date', 'after_or_equal:date_from'])]
        public readonly ?string $dateTo = null,

        #[Rule(['nullable', 'exists:drop_points,id'])]
        public readonly ?string $dropPointId = null,

        #[Rule(['nullable', 'in:orders,products'])]
        public readonly ?string $type = null,
    ) {}
}
