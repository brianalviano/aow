<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DTOs\Product\ProductData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Handles admin CRUD operations for products.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Display a listing of products.
     */
    public function index(): Response
    {
        $products = $this->productService->getPaginated();

        return Inertia::render('Domains/Admin/Product/Index', [
            'products' => ProductResource::collection($products),
        ]);
    }

    /**
     * Show the form for creating a new product.
     */
    public function create(): Response
    {
        $categories = ProductCategory::orderBy('sort_order', 'asc')->orderBy('name', 'asc')->get();

        return Inertia::render('Domains/Admin/Product/Form', [
            'productCategories' => ProductCategoryResource::collection($categories),
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        try {
            $data = ProductData::fromStoreRequest($request);

            $this->productService->createProduct($data);

            Inertia::flash('toast', [
                'message' => 'Product berhasil dibuat',
                'type' => 'success',
            ]);

            return redirect()->route('admin.products.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat Product: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): Response
    {
        // Ensuring category relation is loaded but handled mostly in Resource
        $product->load('productCategory');
        $categories = ProductCategory::orderBy('sort_order', 'asc')->orderBy('name', 'asc')->get();

        return Inertia::render('Domains/Admin/Product/Form', [
            'product' => new ProductResource($product),
            'productCategories' => ProductCategoryResource::collection($categories),
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        try {
            $data = ProductData::fromUpdateRequest($request);

            $this->productService->updateProduct($product, $data);

            Inertia::flash('toast', [
                'message' => 'Product berhasil diperbarui',
                'type' => 'success',
            ]);

            return redirect()->route('admin.products.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui Product: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back()->withInput();
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        try {
            $this->productService->deleteProduct($product);

            Inertia::flash('toast', [
                'message' => 'Product berhasil dihapus',
                'type' => 'success',
            ]);

            return redirect()->route('admin.products.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus Product: ' . $e->getMessage(),
                'type' => 'error',
            ]);

            return back();
        }
    }
}
