<?php

declare(strict_types=1);

namespace App\Http\Controllers\Pic;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PicOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Controller for PIC order operations: approve arrival, send to customer, mark delivered.
 */
class OrderController extends Controller
{
    /**
     * @param PicOrderService $service
     */
    public function __construct(
        private readonly PicOrderService $service,
    ) {}

    /**
     * PIC approves that order has arrived at the pickup point.
     *
     * @param Order $order
     * @return RedirectResponse
     */
    public function approve(Order $order): RedirectResponse
    {
        $officer = Auth::guard('pickup_officer')->user();

        // Verify order belongs to this officer's pickup point
        if ($order->pick_up_point_id !== $officer->pick_up_point_id) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Pesanan ini bukan milik pickup point Anda.',
            ]);
            return redirect()->back();
        }

        try {
            $this->service->approveArrival($order, $officer);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Pesanan berhasil dikonfirmasi sudah sampai.',
            ]);
        } catch (\Throwable $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Gagal mengkonfirmasi pesanan: ' . $e->getMessage(),
            ]);
        }

        return redirect()->back();
    }

    /**
     * PIC sends order to customer (instant: via Biteship, pre-order: manual).
     *
     * @param Order $order
     * @return RedirectResponse
     */
    public function send(Order $order): RedirectResponse
    {
        $officer = Auth::guard('pickup_officer')->user();

        if ($order->pick_up_point_id !== $officer->pick_up_point_id) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Pesanan ini bukan milik pickup point Anda.',
            ]);
            return redirect()->back();
        }

        try {
            $this->service->sendToCustomer($order, $officer);

            $message = $order->isInstant()
                ? 'Kurir berhasil dipesan. Pantau status pengiriman di dashboard.'
                : 'Pesanan ditandai sedang dikirim ke drop point.';

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => $message,
            ]);
        } catch (\Throwable $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Gagal mengirim pesanan: ' . $e->getMessage(),
            ]);
        }

        return redirect()->back();
    }

    /**
     * PIC marks pre-order as delivered/completed.
     *
     * @param Order $order
     * @return RedirectResponse
     */
    public function complete(Order $order): RedirectResponse
    {
        $officer = Auth::guard('pickup_officer')->user();

        if ($order->pick_up_point_id !== $officer->pick_up_point_id) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Pesanan ini bukan milik pickup point Anda.',
            ]);
            return redirect()->back();
        }

        try {
            $this->service->markDelivered($order, $officer);

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => 'Pesanan berhasil ditandai selesai.',
            ]);
        } catch (\Throwable $e) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => 'Gagal menandai pesanan selesai: ' . $e->getMessage(),
            ]);
        }

        return redirect()->back();
    }
}
