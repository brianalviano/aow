<?php

namespace App\DTOs\Schedule;

class ScheduleEntryData
{
    public function __construct(
        public string $userId,
        public string $date,
        public ?string $shiftId,
    ) {}

    public static function fromArray(array $entry): self
    {
        return new self(
            userId: (string) $entry['user_id'],
            date: (string) $entry['date'],
            shiftId: array_key_exists('shift_id', $entry) && $entry['shift_id'] !== null && $entry['shift_id'] !== ''
                ? (string) $entry['shift_id']
                : null,
        );
    }
}
