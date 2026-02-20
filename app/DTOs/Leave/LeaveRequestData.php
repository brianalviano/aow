<?php

namespace App\DTOs\Leave;

use App\Http\Requests\Leave\StoreLeaveRequest;
use App\Http\Requests\Leave\UpdateLeaveRequest;

class LeaveRequestData
{
    public string $startDate;
    public string $endDate;
    public string $type;
    public string $reason;

    public function __construct(string $startDate, string $endDate, string $type, string $reason)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->type = $type;
        $this->reason = $reason;
    }

    public static function fromStoreRequest(StoreLeaveRequest $request): self
    {
        return new self(
            (string) $request->input('start_date'),
            (string) $request->input('end_date'),
            (string) $request->input('type'),
            (string) $request->input('reason'),
        );
    }

    public static function fromUpdateRequest(UpdateLeaveRequest $request): self
    {
        return new self(
            (string) $request->input('start_date'),
            (string) $request->input('end_date'),
            (string) $request->input('type'),
            (string) $request->input('reason'),
        );
    }
}
