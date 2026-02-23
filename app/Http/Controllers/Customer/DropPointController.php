<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\DropPointResource;
use App\Models\DropPoint;
use Inertia\Inertia;
use Inertia\Response;

class DropPointController extends Controller
{
    /**
     * Display the specified drop point detail page.
     */
    public function show(string $id): Response
    {
        $dropPoint = DropPoint::query()
            ->where('is_active', true)
            ->findOrFail($id);

        return Inertia::render('Domains/Customer/DropPoint/Show', [
            'dropPoint' => DropPointResource::make($dropPoint)->resolve(),
        ]);
    }
    /**
     * Display the specified drop point products page.
     */
    public function products(string $id): Response
    {
        $dropPoint = DropPoint::query()
            ->where('is_active', true)
            ->findOrFail($id);

        $categories = \App\Models\ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $products = \App\Models\Product::with(['productCategory', 'productOptions' => function ($query) {
            $query->orderBy('sort_order')->with(['items' => function ($query) {
                $query->orderBy('sort_order');
            }]);
        }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Domains/Customer/DropPoint/Products', [
            'dropPoint' => DropPointResource::make($dropPoint)->resolve(),
            'categories' => \App\Http\Resources\ProductCategoryResource::collection($categories)->resolve(),
            'products' => \App\Http\Resources\ProductResource::collection($products)->resolve(),
        ]);
    }
}
