<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\PaymentProofUploadedAdminMail;
use App\Models\{CompanyProfile, Order};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentProofUploadedAdminNotification extends Notification implements ShouldQueue
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

    public function toMail(object $notifiable): PaymentProofUploadedAdminMail
    {
        $companyName = CompanyProfile::query()->first()?->name ?? 'AOW';

        return (new PaymentProofUploadedAdminMail($this->order, $companyName, $notifiable))
            ->to($notifiable->routeNotificationFor('mail', $this) ?: $notifiable->email);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id'     => $this->order->id,
            'order_number' => $this->order->number,
            'status'       => 'payment_proof_uploaded',
            'message'      => "Customer {$this->order->customer->name} telah mengunggah bukti pembayaran untuk pesanan #{$this->order->number}. Harap segera diverifikasi.",
            'url'          => '/admin/orders/' . $this->order->id,
        ];
    }
}
