<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\PaymentProofUploadedCustomerMail;
use App\Models\{CompanyProfile, Order};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentProofUploadedCustomerNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (!empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): PaymentProofUploadedCustomerMail
    {
        $companyName = CompanyProfile::query()->first()?->name ?? 'AOW';

        return (new PaymentProofUploadedCustomerMail($this->order, $companyName))
            ->to($notifiable->routeNotificationFor('mail', $this) ?: $notifiable->email);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id'     => $this->order->id,
            'order_number' => $this->order->number,
            'status'       => 'payment_proof_uploaded',
            'message'      => "Bukti pembayaran pesanan #{$this->order->number} berhasil diunggah dan sedang menunggu verifikasi admin.",
            'url'          => '/orders/' . $this->order->id,
        ];
    }
}
