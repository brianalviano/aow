<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PurchaseOrderPendingHoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private PurchaseOrder $purchaseOrder;

    /**
     * Notifikasi ketika Purchase Order memasuki status Pending HO Approval.
     *
     * @param PurchaseOrder $purchaseOrder
     */
    public function __construct(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }

    /**
     * Channel pengiriman notifikasi.
     *
     * @param object $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Konten email notifikasi.
     *
     * @param object $notifiable
     * @return MailMessage
     */
    public function toMail(object $notifiable): MailMessage
    {
        $po = $this->purchaseOrder->loadMissing(['supplier', 'warehouse']);
        $number = (string) ($po->number ?? '');
        $supplier = (string) ($po->supplier?->name ?? '-');
        $warehouse = (string) ($po->warehouse?->name ?? '-');
        $grandTotal = (int) ($po->grand_total ?? 0);
        $url = route('purchase-orders.show', $po->getKey());

        return (new MailMessage())
            ->subject('PO Menunggu Persetujuan HO: ' . $number)
            ->greeting('Halo ' . (string) ($notifiable->name ?? 'Tim HO') . ',')
            ->line('Terdapat Purchase Order yang memerlukan persetujuan HO.')
            ->line('Nomor: ' . $number)
            ->line('Pemasok: ' . $supplier)
            ->line('Gudang: ' . $warehouse)
            ->line('Grand Total: Rp ' . number_format($grandTotal, 0, ',', '.'))
            ->action('Buka Purchase Order', $url);
    }

    /**
     * Payload notifikasi untuk penyimpanan di database.
     *
     * @param object $notifiable
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $po = $this->purchaseOrder;
        return [
            'title' => 'PO Menunggu Persetujuan HO',
            'message' => 'Purchase Order ' . (string) ($po->number ?? '') . ' memerlukan persetujuan HO.',
            'url' => route('purchase-orders.show', $po->getKey()),
            'type' => 'purchase_order_pending_ho',
            'priority' => 'high',
            'data' => [
                'purchase_order_id' => (string) $po->getKey(),
                'number' => (string) ($po->number ?? ''),
            ],
        ];
    }
}
