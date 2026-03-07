<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\PickUpPointOfficer\PickUpPointOfficerData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PickUpPointOfficer\StorePickUpPointOfficerRequest;
use App\Http\Requests\Admin\PickUpPointOfficer\UpdatePickUpPointOfficerRequest;
use App\Http\Resources\PickUpPointOfficerResource;
use App\Models\PickUpPoint;
use App\Models\PickUpPointOfficer;
use App\Services\PickUpPointOfficerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PickUpPointOfficerController extends Controller
{
    public function __construct(
        private readonly PickUpPointOfficerService $pickUpPointOfficerService
    ) {}

    /**
     * Display a listing of the pick up point officers.
     */
    public function index(Request $request): Response
    {
        $limit = $request->integer('limit', 15);
        $search = $request->input('search');

        $officers = $this->pickUpPointOfficerService->getPaginated(
            limit: $limit > 0 ? $limit : 15,
            search: $search
        );

        return Inertia::render('Domains/Admin/PickUpPointOfficer/Index', [
            'officers' => PickUpPointOfficerResource::collection($officers),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new pick up point officer.
     */
    public function create(): Response
    {
        $pickUpPoints = PickUpPoint::orderBy('name')->get(['id', 'name']);

        return Inertia::render('Domains/Admin/PickUpPointOfficer/Form', [
            'pickUpPoints' => $pickUpPoints,
        ]);
    }

    /**
     * Store a newly created pick up point officer in storage.
     */
    public function store(StorePickUpPointOfficerRequest $request): RedirectResponse
    {
        $data = PickUpPointOfficerData::fromStoreRequest($request);

        $this->pickUpPointOfficerService->createPickUpPointOfficer($data);

        return redirect()->route('admin.pick-up-point-officers.index')
            ->with('success', 'Berhasil menambahkan Pick Up Point Officer baru.');
    }

    /**
     * Show the form for editing the specified pick up point officer.
     */
    public function edit(PickUpPointOfficer $pickUpPointOfficer): Response
    {
        $pickUpPoints = PickUpPoint::orderBy('name')->get(['id', 'name']);
        // Load the assigned pick up point
        $pickUpPointOfficer->load('pickUpPoint');

        return Inertia::render('Domains/Admin/PickUpPointOfficer/Form', [
            'officer' => new PickUpPointOfficerResource($pickUpPointOfficer),
            'pickUpPoints' => $pickUpPoints,
        ]);
    }

    /**
     * Update the specified pick up point officer in storage.
     */
    public function update(UpdatePickUpPointOfficerRequest $request, PickUpPointOfficer $pickUpPointOfficer): RedirectResponse
    {
        $data = PickUpPointOfficerData::fromUpdateRequest($request);

        $this->pickUpPointOfficerService->updatePickUpPointOfficer($pickUpPointOfficer, $data);

        return redirect()->route('admin.pick-up-point-officers.index')
            ->with('success', 'Berhasil memperbarui Pick Up Point Officer.');
    }

    /**
     * Remove the specified pick up point officer from storage.
     */
    public function destroy(PickUpPointOfficer $pickUpPointOfficer): RedirectResponse
    {
        $this->pickUpPointOfficerService->deletePickUpPointOfficer($pickUpPointOfficer);

        return redirect()->back()
            ->with('success', 'Berhasil menghapus Pick Up Point Officer.');
    }
}
