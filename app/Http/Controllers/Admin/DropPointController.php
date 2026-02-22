<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\DropPoint\DropPointData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DropPoint\StoreDropPointRequest;
use App\Http\Requests\Admin\DropPoint\UpdateDropPointRequest;
use App\Http\Resources\DropPointResource;
use App\Models\DropPoint;
use App\Services\DropPointService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Handles admin CRUD operations for drop points.
 */
class DropPointController extends Controller
{
    public function __construct(
        private readonly DropPointService $dropPointService
    ) {}

    /**
     * Display a listing of drop points.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $limit = (int) $request->query('limit', 15);

        $dropPoints = $this->dropPointService->getPaginated($limit, $search);

        return Inertia::render('Domains/Admin/DropPoint/Index', [
            'dropPoints' => DropPointResource::collection($dropPoints),
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

    /**
     * Show the form for creating a new drop point.
     */
    public function create(): Response
    {
        return Inertia::render('Domains/Admin/DropPoint/Form', [
            // Return configuration for the map
            'tomtomApiKey' => config('tomtom.api_key'),
            'defaultCenter' => [
                'lat' => config('tomtom.geofence.center_lat'),
                'lng' => config('tomtom.geofence.center_long'),
            ],
        ]);
    }

    /**
     * Store a newly created drop point.
     */
    public function store(StoreDropPointRequest $request): RedirectResponse
    {
        try {
            $data = DropPointData::fromStoreRequest($request);

            $this->dropPointService->createDropPoint($data);

            Inertia::flash('toast', [
                'message' => 'Drop Point berhasil dibuat',
                'type' => 'success',
            ]);

            return redirect()->route('admin.drop-points.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat Drop Point: '.$e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Show the form for editing the specified drop point.
     */
    public function edit(DropPoint $dropPoint): Response
    {
        return Inertia::render('Domains/Admin/DropPoint/Form', [
            'dropPoint' => new DropPointResource($dropPoint),
            'tomtomApiKey' => config('tomtom.api_key'),
            'defaultCenter' => [
                'lat' => config('tomtom.geofence.center_lat'),
                'lng' => config('tomtom.geofence.center_long'),
            ],
        ]);
    }

    /**
     * Update the specified drop point.
     */
    public function update(UpdateDropPointRequest $request, DropPoint $dropPoint): RedirectResponse
    {
        try {
            $data = DropPointData::fromUpdateRequest($request);

            $this->dropPointService->updateDropPoint($dropPoint, $data);

            Inertia::flash('toast', [
                'message' => 'Drop Point berhasil diperbarui',
                'type' => 'success',
            ]);

            return redirect()->route('admin.drop-points.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui Drop Point: '.$e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Remove the specified drop point.
     */
    public function destroy(DropPoint $dropPoint): RedirectResponse
    {
        try {
            $this->dropPointService->deleteDropPoint($dropPoint);

            Inertia::flash('toast', [
                'message' => 'Drop Point berhasil dihapus',
                'type' => 'success',
            ]);

            return redirect()->route('admin.drop-points.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus Drop Point: '.$e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }
}
