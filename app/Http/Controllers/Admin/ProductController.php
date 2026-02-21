<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles admin CRUD operations for products.
 */
class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(): Response
    {
        return Inertia::render('Domains/Admin/Product/Index');
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): Response
    {
        return Inertia::render('Domains/Admin/Product/Create');
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('admin.products.index');
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(int $id): Response
    {
        return Inertia::render('Domains/Admin/Product/Edit');
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('admin.products.index');
    }

    /**
     * Remove the specified product.
     */
    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('admin.products.index');
    }
}
