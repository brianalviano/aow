<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;

/**
 * Data Transfer Object for customer feedback submissions.
 *
 * @property string $customerId The ID of the customer providing feedback.
 * @property string $type The type of feedback (kritik, saran, lainnya).
 * @property string $content The feedback message content.
 */
class FeedbackDTO extends Data
{
    public function __construct(
        public readonly string $customerId,

        #[Rule('required', 'string', 'in:kritik,saran,lainnya')]
        public readonly string $type,

        #[Rule('required', 'string', 'min:10', 'max:2000')]
        public readonly string $content,
    ) {}

    /**
     * Custom validation messages for feedback fields.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'type.in' => 'Tipe feedback harus berupa kritik, saran, atau lainnya.',
            'content.min' => 'Konten feedback minimal 10 karakter.',
            'content.max' => 'Konten feedback maksimal 2000 karakter.',
        ];
    }
}
