<?php

declare(strict_types=1);

namespace App\DTOs\PaymentGuide;

use Spatie\LaravelData\{Attributes\Validation\Rule, Data};
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * DTO for payment guide creation/update.
 *
 * Store and Update share identical rules, so a single DTO is used.
 * Content is a nested array of sections with titles and items.
 */
class PaymentGuideData extends Data
{
    public function __construct(
        #[Rule(['required', 'string', 'max:255'])]
        public readonly string $name,

        #[Rule(['required', 'array'])]
        public readonly array $content,
    ) {}

    /**
     * Nested array validation for content structure.
     *
     * @return array<string, array<mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'content.*.title' => ['required', 'string', 'max:255'],
            'content.*.items' => ['required', 'array'],
            'content.*.items.*' => ['required', 'string'],
        ];
    }
}
