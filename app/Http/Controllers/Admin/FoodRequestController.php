<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FoodRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class FoodRequestController
 * 
 * Handles management of customer food requests from the admin side.
 */
class FoodRequestController extends Controller
{
    /**
     * Display a listing of the food requests.
     *
     * @return Response
     */
    public function index(): Response
    {
        $requests = FoodRequest::with('customer')
            ->latest()
            ->paginate(10);

        return Inertia::render('Domains/Admin/FoodRequest/Index', [
            'requests' => $requests,
        ]);
    }

    /**
     * Update the status of a food request.
     *
     * @param Request $request
     * @param FoodRequest $foodRequest
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function update(Request $request, FoodRequest $foodRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,approved,rejected'],
        ]);

        try {
            $foodRequest->update([
                'status' => $validated['status'],
            ]);

            Inertia::flash('toast', [
                'message' => 'Status permintaan berhasil diperbarui',
                'type' => 'success',
            ]);

            return back();
        } catch (\Throwable $e) {
            Log::error('Failed to update food request status', [
                'food_request_id' => $foodRequest->id,
                'status'          => $validated['status'],
                'error'           => $e->getMessage(),
            ]);

            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui status: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            throw $e;
        }
    }
}
