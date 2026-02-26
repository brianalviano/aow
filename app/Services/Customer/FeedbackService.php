<?php

declare(strict_types=1);

namespace App\Services\Customer;

use App\DTOs\Customer\FeedbackDTO;
use App\Models\Feedback;
use Illuminate\Support\Facades\{DB, Log};
use Throwable;

/**
 * Service for handling customer feedback operations.
 */
class FeedbackService
{
    /**
     * Store a new feedback in the database.
     *
     * @param FeedbackDTO $dto
     * @return Feedback
     * @throws Throwable
     */
    public function store(FeedbackDTO $dto): Feedback
    {
        try {
            return DB::transaction(function () use ($dto) {
                $feedback = Feedback::create([
                    'customer_id' => $dto->customerId,
                    'type'        => $dto->type,
                    'content'     => $dto->content,
                    'is_read'     => false,
                ]);

                Log::info('Feedback created successfully', [
                    'feedback_id' => $feedback->id,
                    'customer_id' => $dto->customerId,
                    'type'        => $dto->type,
                ]);

                return $feedback;
            });
        } catch (Throwable $e) {
            Log::error('Failed to store feedback', [
                'customer_id' => $dto->customerId,
                'payload'     => [
                    'type'    => $dto->type,
                    'content' => $dto->content,
                ],
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
