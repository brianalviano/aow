<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\OrderStatusChangedMail;
use App\Models\{CompanyProfile, Order};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi yang dikirim ke customer ketika status pesanan berubah oleh admin.
 *
 * Channels: database (real-time) + mail (email).
 * Notification bersifat queued agar tidak memblokir request HTTP admin.
 */
class OrderStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param Order  $order     Pesanan yang status-nya berubah (sudah di-refresh setelah update).
     * @param string $newStatus Status baru: confirmed|shipped|delivered|cancelled.
     */
    public function __construct(
        public readonly Order $order,
        public readonly string $newStatus,
    ) {}

    /**
     * Delivery channels for this notification.
     *
     * @param object $notifiable
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Build the mail notification.
     *
     * @param object $notifiable
     * @return \Illuminate\Mail\Mailable
     * @throws \Throwable
     */
    public function toMail(object $notifiable): OrderStatusChangedMail
    {
        $companyName = CompanyProfile::query()->first()?->name ?? 'AOW';

        return new OrderStatusChangedMail($this->order, $this->newStatus, $companyName);
    }

    /**
     * Build the database notification payload.
     *
     * @param object $notifiable
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id'     => $this->order->id,
            'order_number' => $this->order->number,
            'status'       => $this->newStatus,
            'message'      => $this->statusMessage(),
            'url'          => '/orders/' . $this->order->id,
        ];
    }

    /**
     * Generate a human-readable status message in Bahasa Indonesia.
     *
     * @return string
     */
    private function statusMessage(): string
    {
        return match ($this->newStatus) {
            'confirmed' => "Pesanan #{$this->order->number} Anda telah dikonfirmasi dan sedang diproses.",
            'shipped'   => "Pesanan #{$this->order->number} Anda sedang dalam pengiriman.",
            'delivered' => "Pesanan #{$this->order->number} Anda telah selesai. Terima kasih!",
            'cancelled' => "Pesanan #{$this->order->number} Anda telah dibatalkan.",
            default     => "Status pesanan #{$this->order->number} Anda telah diperbarui.",
        };
    }
}
