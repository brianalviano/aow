<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Sales;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\OpenCashierSessionRequest;
use App\Http\Requests\Sales\CloseCashierSessionRequest;
use App\Services\CashierSessionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Throwable;

/**
 * Controller untuk operasi Buka/Tutup Kasir (shift kasir) pada POS.
 *
 * @author PJD
 */
final class CashierSessionController extends Controller
{
    /**
     * Buka kasir (shift kasir) dengan saldo awal laci.
     *
     * @param OpenCashRegisterShiftRequest $request
     * @param CashierSessionService $service
     * @return RedirectResponse
     */
    public function open(OpenCashierSessionRequest $request, CashierSessionService $service): RedirectResponse
    {
        try {
            $service->openShift(
                (string) $request->user()->getAuthIdentifier(),
                (int) $request->input('opening_balance'),
            );
            Inertia::flash('toast', [
                'message' => 'Kasir dibuka. Selamat bertugas!',
                'type' => 'success',
            ]);
            return redirect()->route('pos.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal membuka kasir: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }

    /**
     * Tutup kasir (shift kasir) dengan input saldo kas aktual yang diterima.
     *
     * @param CloseCashRegisterShiftRequest $request
     * @param CashierSessionService $service
     * @return RedirectResponse
     */
    public function close(CloseCashierSessionRequest $request, CashierSessionService $service): RedirectResponse
    {
        try {
            $service->closeShift(
                (string) $request->user()->getAuthIdentifier(),
                (string) $request->input('session_id'),
                (int) $request->input('actual_closing_balance'),
            );
            Inertia::flash('toast', [
                'message' => 'Kasir ditutup. Terima kasih!',
                'type' => 'success',
            ]);
            return redirect()->route('pos.index');
        } catch (Throwable $e) {
            Inertia::flash('toast', [
                'message' => 'Gagal menutup kasir: ' . $e->getMessage(),
                'type' => 'error',
            ]);
            return back();
        }
    }
}
