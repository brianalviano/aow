<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\FoodRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Class FoodRequestController
 * 
 * Handles requests for new food or drinks from customers.
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
        /** @var \App\Models\Customer $user */
        $user = Auth::guard('customer')->user();

        $requests = FoodRequest::where('customer_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('Domains/Customer/FoodRequest/Index', [
            'requests' => $requests,
        ]);
    }

    /**
     * Store a newly created food request.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws \Throwable
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        /** @var \App\Models\Customer $user */
        $user = Auth::guard('customer')->user();

        try {
            FoodRequest::create([
                'customer_id' => $user->id,
                'name'        => $validated['name'],
                'notes'       => $validated['notes'],
                'status'      => 'pending',
            ]);

            return back()->with('success', 'Permintaan makanan baru berhasil dikirim!');
        } catch (\Throwable $e) {
            Log::error('Failed to create food request', [
                'customer_id' => $user->id,
                'payload'     => $validated,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
