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
use App\Services\QuotaService;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly QuotaService $quotaService
    ) {}

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

        return $this->renderProducts(null, $address);
    }

    /**
     * Shared logic to render the products page.
     */
    private function renderProducts(?DropPoint $dropPoint = null, ?array $address = null): Response
    {
        $orderType = session('checkout_order_type', 'preorder');
        $lat = $dropPoint?->latitude ?? ($address['latitude'] ?? null);
        $lng = $dropPoint?->longitude ?? ($address['longitude'] ?? null);

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->whereHas('products', function ($query) use ($orderType) {
                $query->where('is_active', true)->whereHas('chefs', function ($q) use ($orderType) {
                    $q->whereJsonContains('order_types', $orderType);
                });
            })
            ->get();

        $products = Product::with([
            'chefs' => function ($query) use ($orderType, $lat, $lng) {
                $query->whereJsonContains('order_types', $orderType);

                if ($lat !== null && $lng !== null) {
                    // Haversine formula to calculate distance in kilometers
                    $query->select('*')
                        ->selectRaw(
                            '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                            [$lat, $lng, $lat]
                        )
                        ->orderBy('distance');
                }
            },
            'productCategory',
            'productOptions' => function ($query) {
                $query->orderBy('sort_order')->with(['items' => function ($query) {
                    $query->orderBy('sort_order');
                }]);
            }
        ])
            ->where('is_active', true)
            ->whereHas('chefs', function ($query) use ($orderType) {
                $query->whereJsonContains('order_types', $orderType);
            })
            ->orderBy('sort_order')
            ->get();

        $quotaProgress = null;
        if ($dropPoint && $orderType === 'preorder') {
            $deliveryDate = session('checkout_delivery_date', now()->addDay()->format('Y-m-d')); // Default to tomorrow if not set
            $quotaProgress = $this->quotaService->calculateDropPointQuotaProgress($dropPoint->id, $deliveryDate);
        }

        return Inertia::render('Domains/Customer/Product/Index', [
            'dropPoint' => $dropPoint ? DropPointResource::make($dropPoint)->resolve() : null,
            'address' => $address,
            'categories' => ProductCategoryResource::collection($categories)->resolve(),
            'products' => ProductResource::collection($products)->resolve(),
            'savedCart' => (object) session('checkout_cart', []),
            'orderType' => $orderType,
            'quotaProgress' => $quotaProgress,
        ]);
    }

    /**
     * Get paginated testimonials for a product.
     */
    public function testimonials(Product $product): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $merged = $product->getManipulatedTestimonials(50); // Get up to 50 merged items
        return \App\Http\Resources\TestimonialResource::collection($merged);
    }
}
