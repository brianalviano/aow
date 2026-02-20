<?php

namespace App\Http\Controllers\Admin\HR;

use App\DTOs\Leave\LeaveRequestData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Leave\{StoreLeaveRequest, UpdateLeaveRequest};
use App\Http\Resources\LeaveRequestResource;
use App\Enums\{LeaveRequestType, LeaveRequestStatus};
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class SelfLeaveController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = (string) auth()->id();
        $q = (string) $request->string('q')->toString();
        $type = (string) $request->string('type')->toString();
        $status = (string) $request->string('status')->toString();
        $perPage = (int) $request->integer('per_page', 10);

        $query = LeaveRequest::query()
            ->with(['user'])
            ->where('user_id', $userId)
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('reason', 'ilike', "%{$q}%");
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

        return Inertia::render('Domains/Admin/HR/Leaves/Index', [
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

    public function create(): Response
    {
        return Inertia::render('Domains/Admin/HR/Leaves/Form', [
            'leave' => null,
            'types' => array_map(fn($t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ], LeaveRequestType::cases()),
        ]);
    }

    public function store(StoreLeaveRequest $request, LeaveService $service): RedirectResponse
    {
        try {
            $service->create((string) auth()->id(), LeaveRequestData::fromStoreRequest($request));
            Inertia::flash('toast', [
                'message' => 'Permohonan izin berhasil dibuat',
                'type' => 'success',
            ]);
            return redirect()->route('leaves.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat permohonan izin: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function edit(LeaveRequest $leave): Response|RedirectResponse
    {
        if ((string) $leave->user_id !== (string) auth()->id()) {
            Inertia::flash('toast', [
                'message' => 'Tidak berhak mengedit permohonan ini',
                'type' => 'error',
            ]);
            return redirect()->route('leaves.index');
        }

        return Inertia::render('Domains/Admin/HR/Leaves/Form', [
            'leave' => LeaveRequestResource::make($leave)->toArray(request()),
            'types' => array_map(fn($t) => [
                'value' => $t->value,
                'label' => $t->label(),
            ], LeaveRequestType::cases()),
        ]);
    }

    public function update(UpdateLeaveRequest $request, LeaveRequest $leave, LeaveService $service): RedirectResponse
    {
        if ((string) $leave->user_id !== (string) auth()->id()) {
            Inertia::flash('toast', [
                'message' => 'Tidak berhak memperbarui permohonan ini',
                'type' => 'error',
            ]);
            return redirect()->route('leaves.index');
        }
        try {
            $service->update($leave, LeaveRequestData::fromUpdateRequest($request));
            Inertia::flash('toast', [
                'message' => 'Permohonan izin berhasil diperbarui',
                'type' => 'success',
            ]);
            return redirect()->route('leaves.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui permohonan izin: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function destroy(LeaveRequest $leave, LeaveService $service): RedirectResponse
    {
        if ((string) $leave->user_id !== (string) auth()->id()) {
            Inertia::flash('toast', [
                'message' => 'Tidak berhak menghapus permohonan ini',
                'type' => 'error',
            ]);
            return redirect()->route('leaves.index');
        }
        try {
            $service->delete($leave);
            Inertia::flash('toast', [
                'message' => 'Permohonan izin berhasil dihapus',
                'type' => 'success',
            ]);
            return redirect()->route('leaves.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus permohonan izin: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }
}
