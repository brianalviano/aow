<?php

namespace App\Http\Controllers\Admin\HR;

use App\DTOs\Shift\ShiftData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\StoreShiftRequest;
use App\Http\Requests\Shift\UpdateShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Models\Shift;
use App\Services\ShiftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ShiftController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $isOvernightParam = (string) $request->string('is_overnight')->toString();
        $isOffParam = (string) $request->string('is_off')->toString();

        $query = Shift::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where('name', 'ilike', "%{$q}%");
            })
            ->when($isOvernightParam !== '', function ($builder) use ($isOvernightParam) {
                $builder->where('is_overnight', in_array(strtolower($isOvernightParam), ['1', 'true'], true));
            })
            ->when($isOffParam !== '', function ($builder) use ($isOffParam) {
                $builder->where('is_off', in_array(strtolower($isOffParam), ['1', 'true'], true));
            })
            ->orderBy('name');

        $perPage = (int) $request->integer('per_page', 10);
        $shifts = $query->paginate($perPage)->appends([
            'q' => $q,
            'is_overnight' => $isOvernightParam,
            'is_off' => $isOffParam,
        ]);
        $items = array_map(
            fn($s) => ShiftResource::make($s)->toArray($request),
            $shifts->items(),
        );

        return Inertia::render('Domains/Admin/HR/Shifts/Index', [
            'shifts' => $items,
            'meta' => [
                'current_page' => $shifts->currentPage(),
                'per_page' => $shifts->perPage(),
                'total' => $shifts->total(),
                'last_page' => $shifts->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'is_overnight' => $isOvernightParam,
                'is_off' => $isOffParam,
            ],
        ]);
    }

    public function create(): Response
    {
        $hasOff = Shift::query()->where('is_off', true)->exists();
        return Inertia::render('Domains/Admin/HR/Shifts/Form', [
            'shift' => null,
            'hasOff' => $hasOff,
        ]);
    }

    public function store(StoreShiftRequest $request, ShiftService $service): RedirectResponse
    {
        $wantsOff = (bool) $request->boolean('is_off');
        if ($wantsOff) {
            $exists = Shift::query()->where('is_off', true)->exists();
            if ($exists) {
                Inertia::flash('toast', [
                    'message' => 'Shift OFF sudah ada',
                    'type' => 'warning',
                ]);
                return redirect()->route('shifts.index');
            }
        }
        try {
            $service->create(ShiftData::fromStoreRequest($request));
            Inertia::flash('toast', [
                'message' => 'Shift berhasil dibuat',
                'type' => 'success',
            ]);
            return redirect()->route('shifts.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat shift: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return redirect()->route('shifts.index');
        }
    }

    public function show(Shift $shift): Response
    {
        return Inertia::render('Domains/Admin/HR/Shifts/Show', [
            'shift' => ShiftResource::make($shift)->toArray(request()),
        ]);
    }

    public function edit(Shift $shift): Response
    {
        if ($shift->is_off) {
            abort(403);
        }
        $hasOff = Shift::query()->where('is_off', true)->exists();
        return Inertia::render('Domains/Admin/HR/Shifts/Form', [
            'shift' => ShiftResource::make($shift)->toArray(request()),
            'hasOff' => $hasOff,
        ]);
    }

    public function update(UpdateShiftRequest $request, Shift $shift, ShiftService $service): RedirectResponse
    {
        if ($shift->is_off) {
            Inertia::flash('toast', [
                'message' => 'Tidak dapat memperbarui shift OFF',
                'type' => 'warning',
            ]);
            return redirect()->route('shifts.index');
        }
        $wantsOff = (bool) $request->boolean('is_off');
        if ($wantsOff) {
            $existsOtherOff = Shift::query()
                ->where('is_off', true)
                ->where('id', '!=', $shift->getKey())
                ->exists();
            if ($existsOtherOff) {
                Inertia::flash('toast', [
                    'message' => 'Shift OFF lain sudah ada',
                    'type' => 'warning',
                ]);
                return redirect()->route('shifts.index');
            }
        }
        try {
            $service->update($shift, ShiftData::fromUpdateRequest($request));
            Inertia::flash('toast', [
                'message' => 'Shift berhasil diperbarui',
                'type' => 'success',
            ]);
            return redirect()->route('shifts.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui shift: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return redirect()->route('shifts.index');
        }
    }

    public function destroy(Shift $shift, ShiftService $service): RedirectResponse
    {
        if ($shift->is_off) {
            Inertia::flash('toast', [
                'message' => 'Tidak dapat menghapus shift OFF',
                'type' => 'warning',
            ]);
            return redirect()->route('shifts.index');
        }
        try {
            $service->delete($shift);
            Inertia::flash('toast', [
                'message' => 'Shift berhasil dihapus',
                'type' => 'success',
            ]);
            return redirect()->route('shifts.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus shift: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return redirect()->route('shifts.index');
        }
    }
}
