<?php

namespace App\Services;

use App\DTOs\Leave\LeaveRequestData;
use App\Enums\{LeaveRequestStatus, LeaveRequestType, AttendanceStatus};
use App\Models\{LeaveRequest, Schedule, Attendance, User};
use App\Notifications\LeaveRequestApproved;
use App\Notifications\LeaveRequestRejected;
use App\Notifications\NewLeaveRequest;
use App\Traits\RetryableTransactionsTrait;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class LeaveService
{
    use RetryableTransactionsTrait;

    public function __construct(
        private readonly AttendanceService $attendanceService
    ) {}

    public function create(string $userId, LeaveRequestData $data): LeaveRequest
    {
        $start = Carbon::parse($data->startDate)->startOfDay();
        $end = Carbon::parse($data->endDate)->endOfDay();
        if ($end->lt($start)) {
            throw new \DomainException('Tanggal akhir tidak boleh sebelum tanggal mulai');
        }

        $typeOk = in_array($data->type, LeaveRequestType::values(), true);
        if (!$typeOk) {
            throw new \DomainException('Tipe izin tidak valid');
        }

        $leave = $this->runWithRetry(function () use ($userId, $data, $start, $end) {
            return DB::transaction(function () use ($userId, $data, $start, $end) {
                $overlapExists = LeaveRequest::query()
                    ->where('user_id', $userId)
                    ->whereIn('status', [LeaveRequestStatus::Pending, LeaveRequestStatus::Approved])
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                            ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                            ->orWhere(function ($qq) use ($start, $end) {
                                $qq->where('start_date', '<=', $start->toDateString())
                                    ->where('end_date', '>=', $end->toDateString());
                            });
                    })
                    ->exists();
                if ($overlapExists) {
                    throw new \DomainException('Rentang tanggal bertumpang tindih dengan permohonan lain');
                }

                $lr = new LeaveRequest();
                $lr->user_id = $userId;
                $lr->start_date = $data->startDate;
                $lr->end_date = $data->endDate;
                $lr->type = $data->type;
                $lr->reason = $data->reason;
                $lr->status = LeaveRequestStatus::Pending->value;
                $lr->save();
                return $lr;
            }, 3);
        }, 3);

        $admins = User::whereHas('role', function ($q) {
            $q->where('name', 'Super Admin');
        })->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewLeaveRequest($leave));
        }

        return $leave;
    }

    public function update(LeaveRequest $leave, LeaveRequestData $data): LeaveRequest
    {
        if ($leave->status !== LeaveRequestStatus::Pending) {
            throw new \DomainException('Hanya permohonan berstatus pending yang dapat diubah');
        }

        $start = Carbon::parse($data->startDate)->startOfDay();
        $end = Carbon::parse($data->endDate)->endOfDay();
        if ($end->lt($start)) {
            throw new \DomainException('Tanggal akhir tidak boleh sebelum tanggal mulai');
        }
        $typeOk = in_array($data->type, LeaveRequestType::values(), true);
        if (!$typeOk) {
            throw new \DomainException('Tipe izin tidak valid');
        }

        return $this->runWithRetry(function () use ($leave, $data, $start, $end) {
            return DB::transaction(function () use ($leave, $data, $start, $end) {
                $overlapExists = LeaveRequest::query()
                    ->where('user_id', $leave->user_id)
                    ->where('id', '!=', $leave->id)
                    ->whereIn('status', [LeaveRequestStatus::Pending, LeaveRequestStatus::Approved])
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                            ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                            ->orWhere(function ($qq) use ($start, $end) {
                                $qq->where('start_date', '<=', $start->toDateString())
                                    ->where('end_date', '>=', $end->toDateString());
                            });
                    })
                    ->exists();
                if ($overlapExists) {
                    throw new \DomainException('Rentang tanggal bertumpang tindih dengan permohonan lain');
                }

                $leave->start_date = $data->startDate;
                $leave->end_date = $data->endDate;
                $leave->type = $data->type;
                $leave->reason = $data->reason;
                $leave->save();
                return $leave;
            }, 3);
        }, 3);
    }

    public function delete(LeaveRequest $leave): void
    {
        if ($leave->status !== LeaveRequestStatus::Pending) {
            throw new \DomainException('Hanya permohonan berstatus pending yang dapat dihapus');
        }
        $this->runWithRetry(function () use ($leave) {
            return DB::transaction(function () use ($leave) {
                $leave->delete();
                return null;
            }, 3);
        }, 3);
    }

    public function approve(LeaveRequest $leave, string $approverId): LeaveRequest
    {
        if ($leave->status === LeaveRequestStatus::Approved) {
            return $leave;
        }
        if ($leave->status !== LeaveRequestStatus::Pending) {
            throw new \DomainException('Hanya permohonan berstatus pending yang dapat disetujui');
        }

        $approved = $this->runWithRetry(function () use ($leave, $approverId) {
            return DB::transaction(function () use ($leave, $approverId) {
                $leave->status = LeaveRequestStatus::Approved->value;
                $leave->approved_by = $approverId;
                $leave->save();

                $period = CarbonPeriod::create(
                    Carbon::parse($leave->start_date)->toDateString(),
                    Carbon::parse($leave->end_date)->toDateString()
                );
                foreach ($period as $date) {
                    $schedule = Schedule::query()
                        ->with(['shift'])
                        ->where('user_id', $leave->user_id)
                        ->where('date', $date->toDateString())
                        ->first();
                    if (!$schedule) {
                        continue;
                    }
                    if ($schedule->shift && $schedule->shift->is_off) {
                        continue;
                    }
                    $existing = Attendance::query()
                        ->where('schedule_id', $schedule->id)
                        ->first();

                    // Jika sudah ada kehadiran dengan status Present atau Late (sudah absen masuk)
                    if ($existing && in_array($existing->status, [AttendanceStatus::Present, AttendanceStatus::Late], true)) {
                        // Otomatis absen pulang tanpa konfirmasi maps/foto menggunakan AttendanceService
                        $this->attendanceService->autoCheckoutForPermit(
                            $existing,
                            $schedule,
                            $leave->type->label(),
                            $date
                        );
                        continue;
                    }

                    // Jika sudah ada record dengan status Permit, skip
                    if ($existing && $existing->status === AttendanceStatus::Permit) {
                        continue;
                    }

                    $startTime = $schedule->shift?->start_time ? (string) $schedule->shift->start_time : '09:00:00';
                    $endTime = $schedule->shift?->end_time ? (string) $schedule->shift->end_time : null;
                    $checkIn = Carbon::parse($date->format('Y-m-d') . ' ' . $startTime);
                    $checkOut = $endTime ? Carbon::parse($date->format('Y-m-d') . ' ' . $endTime) : null;

                    if ($existing) {
                        $existing->status = AttendanceStatus::Permit->value;
                        $existing->check_in_at = $checkIn;
                        $existing->check_out_at = $checkOut;
                        $existing->check_in_notes = 'Izin ' . $leave->type->label();
                        $existing->save();
                    } else {
                        $a = new Attendance();
                        $a->user_id = (string) $schedule->user_id;
                        $a->schedule_id = (string) $schedule->id;
                        $a->check_in_at = $checkIn;
                        $a->check_out_at = $checkOut;
                        $a->status = AttendanceStatus::Permit->value;
                        $a->check_in_notes = 'Izin ' . $leave->type->label();
                        $a->late_duration = 0;
                        $a->save();
                    }
                }

                return $leave;
            }, 3);
        }, 3);

        Notification::send($approved->user, new LeaveRequestApproved($approved));
        return $approved;
    }

    public function reject(LeaveRequest $leave, string $approverId): LeaveRequest
    {
        if ($leave->status === LeaveRequestStatus::Rejected) {
            return $leave;
        }
        if ($leave->status !== LeaveRequestStatus::Pending) {
            throw new \DomainException('Hanya permohonan berstatus pending yang dapat ditolak');
        }

        $rejected = $this->runWithRetry(function () use ($leave, $approverId) {
            return DB::transaction(function () use ($leave, $approverId) {
                $leave->status = LeaveRequestStatus::Rejected->value;
                $leave->approved_by = $approverId;
                $leave->save();
                return $leave;
            }, 3);
        }, 3);

        Notification::send($rejected->user, new LeaveRequestRejected($rejected));
        return $rejected;
    }

    public function getUserHistory(string $userId)
    {
        return LeaveRequest::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
