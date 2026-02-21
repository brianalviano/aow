<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\ProductCategory\ProductCategoryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductCategory\StoreProductCategoryRequest;
use App\Http\Requests\Admin\ProductCategory\UpdateProductCategoryRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Models\ProductCategory;
use App\Services\ProductCategoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Handles admin CRUD operations for product categories.
 */
class ProductCategoryController extends Controller
{
    public function __construct(
        private readonly ProductCategoryService $productCategoryService
    ) {}

    /**
     * Display a listing of product categories.
     */
    public function index(): Response
    {
        $categories = $this->productCategoryService->getPaginated();

        return Inertia::render('Domains/Admin/ProductCategory/Index', [
            'productCategories' => ProductCategoryResource::collection($categories),
        ]);
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
    public function store(StoreProductCategoryRequest $request): RedirectResponse
    {
        try {
            $data = ProductCategoryData::fromStoreRequest($request);

            $this->productCategoryService->createProductCategory($data);

            Inertia::flash('toast', [
                'message' => 'Product Category berhasil dibuat',
                'type' => 'success',
            ]);

            return redirect()->route('admin.product-categories.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat Product Category: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }

    /**
     * Show the form for editing the specified product category.
     */
    public function edit(ProductCategory $category): Response
    {
        return Inertia::render('Domains/Admin/ProductCategory/Edit', [
            'productCategory' => new ProductCategoryResource($category),
        ]);
    }

    /**
     * Update the specified product category.
     */
    public function update(UpdateProductCategoryRequest $request, ProductCategory $category): RedirectResponse
    {
        try {
            $data = ProductCategoryData::fromUpdateRequest($request);

            $this->productCategoryService->updateProductCategory($category, $data);

            Inertia::flash('toast', [
                'message' => 'Product Category berhasil diperbarui',
                'type' => 'success',
            ]);

            return redirect()->route('admin.product-categories.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui Product Category: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }

    /**
     * Remove the specified product category.
     */
    public function destroy(ProductCategory $category): RedirectResponse
    {
        try {
            $this->productCategoryService->deleteProductCategory($category);

            Inertia::flash('toast', [
                'message' => 'Product Category berhasil dihapus',
                'type' => 'success',
            ]);

            return redirect()->route('admin.product-categories.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus Product Category: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }
}
