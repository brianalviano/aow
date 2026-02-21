<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Handles admin CRUD operations for product categories.
 */
class ProductCategoryController extends Controller
{
    /**
     * Display a listing of product categories.
     */
    public function index(): Response
    {
        return Inertia::render('Domains/Admin/ProductCategory/Index');
    }

    /**
     * Show the form for creating a new product category.
     */
    public function create(): Response
    {
        return Inertia::render('Domains/Admin/ProductCategory/Create');
    }

    /**
     * Store a newly created product category.
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('admin.product-categories.index');
    }

    /**
     * Show the form for editing the specified product category.
     */
    public function edit(int $id): Response
    {
        return Inertia::render('Domains/Admin/ProductCategory/Edit');
    }

    /**
     * Update the specified product category.
     */
    public function update(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('admin.product-categories.index');
    }

    /**
     * Remove the specified product category.
     */
    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        return redirect()->route('admin.product-categories.index');
    }
}
