<?php

declare(strict_types=1);

namespace App\DTOs\Customer;

/**
 * Data Transfer Object for Feedback.
 */
readonly class FeedbackDTO
{
    /**
     * @param string $customerId The ID of the customer providing feedback.
     * @param string $type The type of feedback (kritik, saran, lainnya).
     * @param string $content The feedback message content.
     */
    public function __construct(
        public string $customerId,
        public string $type,
        public string $content,
    ) {}
}
