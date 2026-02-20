<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Discount\{StoreDiscountRequest, UpdateDiscountRequest};
use App\Http\Resources\DiscountResource;
use App\Models\{Discount, Product};
use App\Services\DiscountService;
use App\DTOs\Discount\DiscountData;
use Illuminate\Http\{Request, RedirectResponse};
use Inertia\{Inertia, Response};
use Throwable;

/**
 * Controller untuk Master Data Diskon (Sales).
 *
 * Orkestrasi: validasi → service → response (Inertia).
 */
final class DiscountController extends Controller
{
    /**
     * Daftar diskon dengan filter dan pagination.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        $q = (string) $request->string('q')->toString();
        $type = (string) $request->string('type')->toString();
        $scope = (string) $request->string('scope')->toString();
        $isActive = (string) $request->string('is_active')->toString();

        $query = Discount::query()
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($w) use ($q) {
                    $w->where('name', 'ilike', "%{$q}%");
                });
            })
            ->when($type !== '', function ($builder) use ($type) {
                $builder->where('type', $type);
            })
            ->when($scope !== '', function ($builder) use ($scope) {
                $builder->where('scope', $scope);
            })
            ->when($isActive !== '', function ($builder) use ($isActive) {
                $builder->where('is_active', $isActive === '1' || $isActive === 'true');
            })
            ->orderBy('name');

        $perPage = (int) $request->integer('per_page', 10);
        $discounts = $query->paginate($perPage)->appends([
            'q' => $q,
            'type' => $type,
            'scope' => $scope,
            'is_active' => $isActive,
        ]);

        $items = array_map(
            fn($d) => DiscountResource::make($d)->toArray($request),
            $discounts->items(),
        );

        return Inertia::render('Domains/Admin/Sales/Discounts/Index', [
            'discounts' => $items,
            'meta' => [
                'current_page' => $discounts->currentPage(),
                'per_page' => $discounts->perPage(),
                'total' => $discounts->total(),
                'last_page' => $discounts->lastPage(),
            ],
            'filters' => [
                'q' => $q,
                'type' => $type,
                'scope' => $scope,
                'is_active' => $isActive,
            ],
        ]);
    }

    /**
     * Halaman form pembuatan diskon.
     *
     * @return Response
     */
    public function create(): Response
    {
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku'])
            ->map(fn($p) => [
                'id' => (string) $p->id,
                'name' => (string) $p->name,
                'sku' => (string) ($p->sku ?? ''),
            ])
            ->values()
            ->all();

        return Inertia::render('Domains/Admin/Sales/Discounts/Form', [
            'discount' => null,
            'products' => $products,
        ]);
    }

    /**
     * Simpan diskon baru.
     *
     * @param StoreDiscountRequest $request
     * @param DiscountService $service
     * @return RedirectResponse
     */
    public function store(StoreDiscountRequest $request, DiscountService $service): RedirectResponse
    {
        try {
            $service->create(
                DiscountData::fromStoreRequest($request),
                (string) $request->user()->getAuthIdentifier()
            );
            Inertia::flash('toast', [
                'message' => 'Diskon berhasil dibuat',
                'type' => 'success',
            ]);
            return redirect()->route('discounts.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuat diskon: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    /**
     * Tampilkan detail diskon.
     *
     * @param Discount $discount
     * @return Response
     */
    public function show(Discount $discount): Response
    {
        $discount->load(['items.itemable']);
        return Inertia::render('Domains/Admin/Sales/Discounts/Show', [
            'discount' => DiscountResource::make($discount)->toArray(request()),
        ]);
    }

    /**
     * Halaman edit diskon.
     *
     * @param Discount $discount
     * @return Response
     */
    public function edit(Discount $discount): Response
    {
        $discount->load(['items.itemable']);
        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku'])
            ->map(fn($p) => [
                'id' => (string) $p->id,
                'name' => (string) $p->name,
                'sku' => (string) ($p->sku ?? ''),
            ])
            ->values()
            ->all();

        return Inertia::render('Domains/Admin/Sales/Discounts/Form', [
            'discount' => DiscountResource::make($discount)->toArray(request()),
            'products' => $products,
        ]);
    }

    /**
     * Perbarui diskon.
     *
     * @param UpdateDiscountRequest $request
     * @param Discount $discount
     * @param DiscountService $service
     * @return RedirectResponse
     */
    public function update(UpdateDiscountRequest $request, Discount $discount, DiscountService $service): RedirectResponse
    {
        try {
            $service->update(
                $discount,
                DiscountData::fromUpdateRequest($request),
                (string) $request->user()->getAuthIdentifier()
            );
            Inertia::flash('toast', [
                'message' => 'Diskon berhasil diperbarui',
                'type' => 'success',
            ]);
            return redirect()->route('discounts.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal memperbarui diskon: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    /**
     * Hapus diskon.
     *
     * @param Discount $discount
     * @param Request $request
     * @param DiscountService $service
     * @return RedirectResponse
     */
    public function destroy(Discount $discount, Request $request, DiscountService $service): RedirectResponse
    {
        try {
            $service->delete($discount, (string) $request->user()->getAuthIdentifier());
            Inertia::flash('toast', [
                'message' => 'Diskon berhasil dihapus',
                'type' => 'success',
            ]);
            return redirect()->route('discounts.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus diskon: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return redirect()->route('discounts.index');
        }
    }
}
