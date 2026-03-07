<?php

declare(strict_types=1);

namespace App\DTOs\TestimonialTemplate;

use Spatie\LaravelData\{Attributes\Validation\Rule, Data};

/**
 * DTO for testimonial template creation/update.
 *
 * Store and Update share identical rules, so a single DTO is used.
 */
class TestimonialTemplateData extends Data
{
    public function __construct(
        #[Rule(['required', 'string', 'max:255'])]
        public readonly string $customerName,

        #[Rule(['required', 'integer', 'min:1', 'max:5'])]
        public readonly int $rating,

        #[Rule(['required', 'string'])]
        public readonly string $content,

        #[Rule(['sometimes', 'boolean'])]
        public readonly bool $isActive = true,
    ) {}
}
