<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\DropPointResource;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\DropPoint;
use App\Models\Product;
use App\Models\ProductCategory;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display the specified drop point products page.
     */
    public function index(string $id): Response
    {
        $dropPoint = DropPoint::query()
            ->where('is_active', true)
            ->findOrFail($id);

        return $this->renderProducts($dropPoint);
    }

    /**
     * Display the general products page for custom addresses.
     */
    public function generalIndex(): Response|\Illuminate\Http\RedirectResponse
    {
        $address = session('checkout_address');

        if (!$address) {
            return redirect()->route('home');
        }

        return $this->renderProducts();
    }

    /**
     * Shared logic to render the products page.
     */
    private function renderProducts(?DropPoint $dropPoint = null): Response
    {
        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->whereHas('products', function ($query) {
                $query->where('is_active', true)->whereHas('chefs');
            })
            ->get();

        $products = Product::with(['productCategory', 'productOptions' => function ($query) {
            $query->orderBy('sort_order')->with(['items' => function ($query) {
                $query->orderBy('sort_order');
            }]);
        }])
            ->where('is_active', true)
            ->whereHas('chefs')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Domains/Customer/Product/Index', [
            'dropPoint' => $dropPoint ? DropPointResource::make($dropPoint)->resolve() : null,
            'categories' => ProductCategoryResource::collection($categories)->resolve(),
            'products' => ProductResource::collection($products)->resolve(),
            'savedCart' => session('checkout_cart', []),
        ]);
    }
}
