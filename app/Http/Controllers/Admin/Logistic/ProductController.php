<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Logistic;

use App\DTOs\Product\ProductData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\ImportProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\{Product, ProductCategory, ProductSubCategory, ProductUnit, ProductFactory, ProductSubFactory, ProductCondition};
use App\Services\ProductService;
use App\Exports\ProductsExport;
use App\Exports\ProductsImportTemplateExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;
use Maatwebsite\Excel\Facades\Excel;
use Milon\Barcode\DNS1D;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $query = Product::query()
            ->with([
                'productCategory',
                'productSubCategory',
                'productUnit',
                'productFactory',
                'productSubFactory',
                'productCondition',
                'parentProduct',
            ])
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%")
                        ->orWhere('sku', 'ilike', "%{$q}%")
                        ->orWhere('description', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('name');

        $perPage = (int) $request->integer('per_page', 10);
        $products = $query->paginate($perPage)->appends([
            'q' => $q,
        ]);
        $items = array_map(
            fn($p) => ProductResource::make($p)->toArray($request),
            $products->items(),
        );

        return Inertia::render('Domains/Admin/Logistic/Products/Index', [
            'products' => $items,
            'meta' => [
                'current_page' => $products->currentPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'last_page' => $products->lastPage(),
            ],
            'filters' => [
                'q' => $q,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Domains/Admin/Logistic/Products/Form', [
            'product' => null,
            'options' => $this->getOptions(),
        ]);
    }

    public function store(StoreProductRequest $request, ProductService $service): RedirectResponse
    {
        try {
            $service->create(ProductData::fromStoreRequest($request));
            Inertia::flash('toast', [
                'message' => 'Produk berhasil dibuat',
                'type' => 'success',
            ]);
            return redirect()->route('products.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat produk: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function show(Product $product): Response
    {
        $product->load([
            'productCategory',
            'productSubCategory',
            'productUnit',
            'productFactory',
            'productSubFactory',
            'productCondition',
            'parentProduct',
        ]);

        return Inertia::render('Domains/Admin/Logistic/Products/Show', [
            'product' => ProductResource::make($product)->toArray(request()),
            'barcode_png' => $product->sku ? ('data:image/png;base64,' . (new DNS1D())->getBarcodePNG((string) $product->sku, 'C128', 2, 60)) : null,
        ]);
    }

    public function edit(Product $product): Response
    {
        $product->load([
            'productCategory',
            'productSubCategory',
            'productUnit',
            'productFactory',
            'productSubFactory',
            'productCondition',
            'parentProduct',
        ]);

        return Inertia::render('Domains/Admin/Logistic/Products/Form', [
            'product' => ProductResource::make($product)->toArray(request()),
            'options' => $this->getOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product, ProductService $service): RedirectResponse
    {
        try {
            $service->update($product, ProductData::fromUpdateRequest($request));
            Inertia::flash('toast', [
                'message' => 'Produk berhasil diperbarui',
                'type' => 'success',
            ]);
            return redirect()->route('products.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui produk: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function destroy(Product $product, ProductService $service): RedirectResponse
    {
        try {
            $service->delete($product);
            Inertia::flash('toast', [
                'message' => 'Produk berhasil dihapus',
                'type' => 'success',
            ]);
            return redirect()->route('products.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus produk: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return redirect()->route('products.index');
        }
    }

    public function export(Request $request): BinaryFileResponse
    {
        $q = (string) $request->string('q')->toString();
        return Excel::download(new ProductsExport($q), 'products.xlsx');
    }

    public function import(ImportProductRequest $request, ProductService $service): RedirectResponse
    {
        $file = $request->file('file');
        Excel::import(new \App\Imports\ProductsImport($service), $file);
        return redirect()->route('products.index');
    }

    public function importTemplate(): BinaryFileResponse
    {
        return Excel::download(new ProductsImportTemplateExport(), 'products_import_template.xlsx');
    }

    public function print(Product $product): Response
    {
        $product->load([
            'productCategory',
            'productUnit',
        ]);
        $barcode = $product->sku ? (new DNS1D())->getBarcodePNG((string) $product->sku, 'C128', 2, 80) : null;
        return Inertia::render('Domains/Admin/Logistic/Products/Print', [
            'product' => ProductResource::make($product)->toArray(request()),
            'barcode_png' => $barcode ? ('data:image/png;base64,' . $barcode) : null,
        ]);
    }

    private function getOptions(): array
    {
        $categories = ProductCategory::query()->orderBy('name')->get(['id', 'name']);
        $subCategories = ProductSubCategory::query()->orderBy('name')->get(['id', 'name']);
        $units = ProductUnit::query()->orderBy('code')->get(['id', 'code', 'name']);
        $factories = ProductFactory::query()->orderBy('name')->get(['id', 'name']);
        $subFactories = ProductSubFactory::query()->orderBy('name')->get(['id', 'name']);
        $conditions = ProductCondition::query()->orderBy('name')->get(['id', 'name']);
        $parents = Product::query()->orderBy('name')->get(['id', 'name', 'sku']);

        return [
            'categories' => $categories->map(fn($x) => ['id' => (string) $x->id, 'name' => (string) $x->name]),
            'sub_categories' => $subCategories->map(fn($x) => ['id' => (string) $x->id, 'name' => (string) $x->name]),
            'units' => $units->map(fn($x) => ['id' => (string) $x->id, 'code' => (string) $x->code, 'name' => (string) $x->name]),
            'factories' => $factories->map(fn($x) => ['id' => (string) $x->id, 'name' => (string) $x->name]),
            'sub_factories' => $subFactories->map(fn($x) => ['id' => (string) $x->id, 'name' => (string) $x->name]),
            'conditions' => $conditions->map(fn($x) => ['id' => (string) $x->id, 'name' => (string) $x->name]),
            'parents' => $parents->map(fn($x) => ['id' => (string) $x->id, 'name' => (string) $x->name, 'sku' => (string) $x->sku]),
            'product_types' => [
                ['value' => 'raw', 'label' => 'Bahan Baku'],
                ['value' => 'ready', 'label' => 'Barang Jadi'],
            ],
            'variant_types' => [
                ['value' => 'standalone', 'label' => 'Mandiri'],
                ['value' => 'parent', 'label' => 'Induk'],
                ['value' => 'variant', 'label' => 'Varian'],
            ],
        ];
    }
}
