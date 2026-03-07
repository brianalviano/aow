<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\Chef\{ChefData, ChefTransferData};
use App\Http\Controllers\Controller;
use App\Http\Resources\{ChefResource, ChefTransferResource};
use App\Models\{Chef, Product};
use App\Services\ChefService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\{Inertia, Response};
use Throwable;

/**
 * Handles admin CRUD operations for chef partners.
 *
 * Manages chef profiles, product assignments, sales overview,
 * and transfer history within the admin panel.
 */
class ChefController extends Controller
{
    public function __construct(
        private readonly ChefService $chefService
    ) {}

    /**
     * Display a listing of chefs.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');
        $limit  = (int) $request->query('limit', 15);

        $chefs = $this->chefService->getPaginated($limit, $search);

        return Inertia::render('Domains/Admin/Chef/Index', [
            'chefs'   => ChefResource::collection($chefs),
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Show the form for creating a new chef.
     */
    public function create(): Response
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Domains/Admin/Chef/Form', [
            'products' => $products,
        ]);
    }

    /**
     * Store a newly created chef.
     */
    public function store(ChefData $data): RedirectResponse
    {
        try {
            $this->chefService->createChef($data);

            Inertia::flash('toast', [
                'message' => 'Chef berhasil ditambahkan',
                'type'    => 'success',
            ]);

            return redirect()->route('admin.chefs.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menambahkan Chef: ' . $e->getMessage(),
                'type'    => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Display the specified chef with sales data and transfer history.
     */
    public function show(Chef $chef): Response
    {
        $chef->load(['products', 'transfers' => function ($q) {
            $q->orderByDesc('transferred_at');
        }]);

        $this->chefService->enrichWithSalesData($chef);

        return Inertia::render('Domains/Admin/Chef/Show', [
            'chef' => new ChefResource($chef),
        ]);
    }

    /**
     * Show the form for editing the specified chef.
     */
    public function edit(Chef $chef): Response
    {
        $chef->load('products');

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Domains/Admin/Chef/Form', [
            'chef'     => new ChefResource($chef),
            'products' => $products,
        ]);
    }

    /**
     * Update the specified chef.
     */
    public function update(ChefData $data, Chef $chef): RedirectResponse
    {
        try {
            $this->chefService->updateChef($chef, $data);

            Inertia::flash('toast', [
                'message' => 'Chef berhasil diperbarui',
                'type'    => 'success',
            ]);

            return redirect()->route('admin.chefs.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui Chef: ' . $e->getMessage(),
                'type'    => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Remove the specified chef.
     */
    public function destroy(Chef $chef): RedirectResponse
    {
        try {
            $this->chefService->deleteChef($chef);

            Inertia::flash('toast', [
                'message' => 'Chef berhasil dihapus',
                'type'    => 'success',
            ]);

            return redirect()->route('admin.chefs.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus Chef: ' . $e->getMessage(),
                'type'    => 'error',
            ]);

            return back();
        }
    }

    /**
     * Store a new transfer for the specified chef.
     */
    public function storeTransfer(ChefTransferData $data, Chef $chef): RedirectResponse
    {
        try {
            $this->chefService->createTransfer($chef, $data);

            Inertia::flash('toast', [
                'message' => 'Transfer berhasil dicatat',
                'type'    => 'success',
            ]);

            return redirect()->route('admin.chefs.show', $chef);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal mencatat transfer: ' . $e->getMessage(),
                'type'    => 'error',
            ]);

            return back()->withInput();
        }
    }
}
