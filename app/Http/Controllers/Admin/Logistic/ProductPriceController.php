<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Logistic;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductPrice\StoreProductPricesRequest;
use App\Services\ProductPriceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Halaman manajemen Harga Produk (beli & jual per level).
 *
 * Menyediakan tampilan matriks harga mirip jadwal dan aksi batch simpan.
 *
 * @author PJD
 */
class ProductPriceController extends Controller
{
    /**
     * Tampilkan matriks harga produk.
     *
     * @param Request $request
     * @param ProductPriceService $service
     * @return Response
     */
    public function index(Request $request, ProductPriceService $service): Response
    {
        $q = (string) $request->string('q')->toString();
        $perPage = (int) $request->integer('per_page', 15);
        $matrix = $service->buildMatrix($q, $perPage);

        return Inertia::render('Domains/Admin/Logistic/ProductPrices/Index', [
            'products' => $matrix['products'],
            'levels' => $matrix['levels'],
            'sellingPriceMap' => $matrix['sellingPriceMap'],
            'sellingPriceMainMap' => $matrix['sellingPriceMainMap'],
            'purchasePriceMap' => $matrix['purchasePriceMap'],
            'suppliers' => $matrix['suppliers'],
            'supplierPriceMap' => $matrix['supplierPriceMap'],
            'meta' => $matrix['meta'],
            'filters' => [
                'q' => $q,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Simpan perubahan harga dalam satu batch.
     *
     * @param StoreProductPricesRequest $request
     * @param ProductPriceService $service
     * @return RedirectResponse
     */
    public function store(StoreProductPricesRequest $request, ProductPriceService $service): RedirectResponse
    {
        $dto = \App\DTOs\ProductPrice\ProductPriceBatchData::fromRequest($request);
        try {
            $service->saveBatch($dto);
            Inertia::flash('toast', [
                'message' => 'Harga produk berhasil disimpan',
                'type' => 'success',
            ]);
            return redirect()->route('product-prices.index', [
                'q' => $dto->q,
            ]);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menyimpan harga produk: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    /**
     * Tambah level harga jual.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function storeLevel(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);
        try {
            $name = (string) $validated['name'];
            $exists = \App\Models\SellingPriceLevel::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->exists();
            if (!$exists) {
                \App\Models\SellingPriceLevel::create(['name' => $name]);
            }
            Inertia::flash('toast', [
                'message' => 'Level harga jual berhasil ditambahkan',
                'type' => 'success',
            ]);
            return redirect()->route('product-prices.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menambah level harga: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    /**
     * Hapus level harga jual beserta seluruh harga produk pada level tersebut.
     *
     * @param string $level
     * @param ProductPriceService $service
     * @return RedirectResponse
     */
    public function destroyLevel(string $level, ProductPriceService $service): RedirectResponse
    {
        try {
            $service->deleteLevel($level);
            Inertia::flash('toast', [
                'message' => 'Level harga jual berhasil dihapus',
                'type' => 'success',
            ]);
            return redirect()->route('product-prices.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menghapus level harga: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function adjustLevel(string $level, Request $request, ProductPriceService $service): RedirectResponse
    {
        $validated = $request->validate([
            'percent' => ['required', 'numeric'],
        ]);
        try {
            $percent = (float) $validated['percent'];
            $service->adjustSellingLevelPricesFromMain($level, $percent);
            Inertia::flash('toast', [
                'message' => 'Persentase harga level berhasil diterapkan',
                'type' => 'success',
            ]);
            return redirect()->route('product-prices.index', [
                'q' => (string) $request->string('q')->toString(),
            ]);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menerapkan persentase harga level: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    public function adjustSupplier(string $supplier, Request $request, ProductPriceService $service): RedirectResponse
    {
        $validated = $request->validate([
            'percent' => ['required', 'numeric'],
        ]);
        try {
            $percent = (float) $validated['percent'];
            $service->adjustSupplierPricesFromMain($supplier, $percent);
            Inertia::flash('toast', [
                'message' => 'Persentase harga supplier berhasil diterapkan',
                'type' => 'success',
            ]);
            return redirect()->route('product-prices.index', [
                'q' => (string) $request->string('q')->toString(),
            ]);
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menerapkan persentase harga supplier: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }
}
