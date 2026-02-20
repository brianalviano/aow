<?php

namespace App\Http\Controllers\Admin\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\ApproveLeaveRequest;
use App\Http\Resources\LeaveRequestResource;
use App\Enums\{LeaveRequestType, LeaveRequestStatus};
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class LeaveController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $type = (string) $request->string('type')->toString();
        $status = (string) $request->string('status')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        $query = LeaveRequest::query()
            ->with(['user'])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('reason', 'ilike', "%{$q}%")
                        ->orWhereHas('user', function ($u) use ($q) {
                            $u->where('name', 'ilike', "%{$q}%")
                                ->orWhere('email', 'ilike', "%{$q}%");
                        });
                });
            })
            ->when($type !== '', function ($builder) use ($type) {
                $builder->where('type', $type);
            })
            ->when($status !== '', function ($builder) use ($status) {
                $builder->where('status', $status);
            })
            ->orderByDesc('start_date');

        $leaves = $query->paginate($perPage)->appends([
            'q' => $q,
            'type' => $type,
            'status' => $status,
        ]);
        $items = array_map(
            fn($l) => LeaveRequestResource::make($l)->toArray($request),
            $leaves->items(),
        );

        return Inertia::render('Domains/Admin/HR/Leaves/Manage', [
            'leaves' => $items,
            'meta' => [
                'current_page' => $leaves->currentPage(),
                'per_page' => $leaves->perPage(),
                'total' => $leaves->total(),
                'last_page' => $leaves->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'type' => $type,
                'status' => $status,
            ],
            'types' => array_map(fn($t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ], LeaveRequestType::cases()),
            'statuses' => array_map(fn($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ], LeaveRequestStatus::cases()),
        ]);
    }

    public function approve(ApproveLeaveRequest $request, LeaveRequest $leave, LeaveService $service): RedirectResponse
    {
        if ($request->input('status') !== LeaveRequestStatus::Approved->value) {
            return $this->reject($leave, $service);
        }
        try {
            $service->approve($leave, (string) auth()->id());
            Inertia::flash('toast', [
                'message' => 'Permohonan izin disetujui',
                'type' => 'success',
            ]);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menyetujui: ' . $e->getMessage(),
                'type' => 'error',
            ]);
        }
        return redirect()->route('leaves.manage.index');
    }

    public function reject(LeaveRequest $leave, LeaveService $service): RedirectResponse
    {
        try {
            $service->reject($leave, (string) auth()->id());
            Inertia::flash('toast', [
                'message' => 'Permohonan izin ditolak',
                'type' => 'warning',
            ]);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menolak: ' . $e->getMessage(),
                'type' => 'error',
            ]);
        }
        return redirect()->route('leaves.manage.index');
    }
}
