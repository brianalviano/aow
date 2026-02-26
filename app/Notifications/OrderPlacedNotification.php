<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\{CompanyProfile, Order};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi yang dikirim ke customer ketika pesanan baru berhasil dibuat.
 *
 * Channel database saja — email sudah dikirim terpisah via OrderPlacedMail.
 * Implements ShouldQueue agar tidak memblokir request HTTP checkout.
 */
class OrderPlacedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param Order $order Pesanan yang baru saja dibuat.
     */
    public function __construct(
        public readonly Order $order,
    ) {}

    /**
     * Delivery channels for this notification.
     *
     * @param object $notifiable
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
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
            'status'       => 'placed',
            'message'      => "Pesanan #{$this->order->number} Anda berhasil dibuat. Terima kasih!",
            'url'          => '/orders/' . $this->order->id,
        ];
    }
}
