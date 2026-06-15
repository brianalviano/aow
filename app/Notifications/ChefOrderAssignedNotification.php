<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\ChefOrderAssignedMail;
use App\Models\CompanyProfile;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi yang dikirim ke chef ketika pesanan ditugaskan kepada mereka.
 *
 * Channels: database + mail.
 */
class ChefOrderAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Order  $order  Pesanan yang ditugaskan.
     */
    public function __construct(
        public readonly Order $order,
    ) {}

    /**
     * Delivery channels.
     *
     * @return array<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Build the mail notification.
     */
    public function toMail(object $notifiable): ChefOrderAssignedMail
    {
        $companyName = CompanyProfile::query()->first()?->name ?? 'AOW';

        return (new ChefOrderAssignedMail($this->order, $notifiable, $companyName))
            ->to($notifiable->routeNotificationFor('mail', $this) ?: $notifiable->email);
    }

    /**
     * Build the database notification payload.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->number,
            'message' => "Anda memiliki pesanan baru (#{$this->order->number}) yang menunggu konfirmasi.",
            'url' => '/chef', // Dashboard chef
        ];
    }
}
