<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\PickUpPoint\PickUpPointData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PickUpPoint\StorePickUpPointRequest;
use App\Http\Requests\Admin\PickUpPoint\UpdatePickUpPointRequest;
use App\Http\Resources\PickUpPointResource;
use App\Models\PickUpPoint;
use App\Models\PickUpPointOfficer;
use App\Services\PickUpPointService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Handles admin CRUD operations for pick up points.
 */
class PickUpPointController extends Controller
{
    public function __construct(
        private readonly PickUpPointService $pickUpPointService
    ) {}

    /**
     * Display a listing of pick up points.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $limit = (int) $request->query('limit', 15);

        $pickUpPoints = $this->pickUpPointService->getPaginated($limit, $search);

        return Inertia::render('Domains/Admin/PickUpPoint/Index', [
            'pickUpPoints' => PickUpPointResource::collection($pickUpPoints),
            'filters' => [
                'search' => $search,
            ],
            'tomtomApiKey' => config('tomtom.api_key'),
            'defaultCenter' => [
                'lat' => config('tomtom.geofence.center_lat'),
                'lng' => config('tomtom.geofence.center_long'),
            ],
        ]);
    }

    public function create(): Response
    {
        $officers = PickUpPointOfficer::orderBy('name')->get(['id', 'name', 'pick_up_point_id']);

        return Inertia::render('Domains/Admin/PickUpPoint/Form', [
            'officers' => $officers,
            'tomtomApiKey' => config('tomtom.api_key'),
            'defaultCenter' => [
                'lat' => config('tomtom.geofence.center_lat'),
                'lng' => config('tomtom.geofence.center_long'),
            ],
        ]);
    }

    /**
     * Store a newly created pick up point.
     */
    public function store(StorePickUpPointRequest $request): RedirectResponse
    {
        try {
            $data = PickUpPointData::fromStoreRequest($request);

            $this->pickUpPointService->createPickUpPoint($data);

            Inertia::flash('toast', [
                'message' => 'Pick Up Point berhasil dibuat',
                'type' => 'success',
            ]);

            return redirect()->route('admin.pick-up-points.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat Pick Up Point: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Show the form for editing the specified pick up point.
     */
    public function edit(PickUpPoint $pickUpPoint): Response
    {
        $pickUpPoint->load('officers:id,name,pick_up_point_id');
        $officers = PickUpPointOfficer::orderBy('name')->get(['id', 'name', 'pick_up_point_id']);

        return Inertia::render('Domains/Admin/PickUpPoint/Form', [
            'pickUpPoint' => new PickUpPointResource($pickUpPoint),
            'assignedOfficerIds' => $pickUpPoint->officers->pluck('id')->toArray(),
            'officers' => $officers,
            'tomtomApiKey' => config('tomtom.api_key'),
            'defaultCenter' => [
                'lat' => config('tomtom.geofence.center_lat'),
                'lng' => config('tomtom.geofence.center_long'),
            ],
        ]);
    }

    /**
     * Update the specified pick up point.
     */
    public function update(UpdatePickUpPointRequest $request, PickUpPoint $pickUpPoint): RedirectResponse
    {
        try {
            $data = PickUpPointData::fromUpdateRequest($request);

            $this->pickUpPointService->updatePickUpPoint($pickUpPoint, $data);

            Inertia::flash('toast', [
                'message' => 'Pick Up Point berhasil diperbarui',
                'type' => 'success',
            ]);

            return redirect()->route('admin.pick-up-points.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui Pick Up Point: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Remove the specified pick up point.
     */
    public function destroy(PickUpPoint $pickUpPoint): RedirectResponse
    {
        try {
            $this->pickUpPointService->deletePickUpPoint($pickUpPoint);

            Inertia::flash('toast', [
                'message' => 'Pick Up Point berhasil dihapus',
                'type' => 'success',
            ]);

            return redirect()->route('admin.pick-up-points.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus Pick Up Point: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }
}
