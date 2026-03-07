<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

use App\Traits\FileHelperTrait;
use Spatie\LaravelData\{Attributes\Validation\Rule, Data};
use Spatie\LaravelData\Support\Validation\ValidationContext;

/**
 * DTO for customer testimonial submission.
 *
 * Handles rating, content, and optional photo upload for order items.
 */
class TestimonialData extends Data
{
    use FileHelperTrait;

    public function __construct(
        #[Rule(['required', 'string', 'in:1,2,3,4,5'])]
        public readonly string $rating,

        #[Rule(['nullable', 'string', 'max:1000'])]
        public readonly ?string $content = null,

        public readonly mixed $photo = null,
    ) {}

    /**
     * Dynamic rules for file validation using FileHelperTrait.
     *
     * @return array<string, array<mixed>>
     */
    public static function rules(ValidationContext $context): array
    {
        $instance = new static(rating: '1');

        return [
            'photo' => $instance->getFileValidationRules(false, ['max_size' => 2 * 1024 * 1024]),
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'rating.required' => 'Rating wajib diisi.',
            'rating.in'       => 'Rating tidak valid.',
            'content.max'     => 'Konten testimoni maksimal 1000 karakter.',
            'photo.image'     => 'Format foto harus berupa gambar.',
            'photo.max'       => 'Ukuran foto maksimal 2MB.',
        ];
    }
}
