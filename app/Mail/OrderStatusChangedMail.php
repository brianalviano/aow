<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;

/**
 * Email dikirim ke customer ketika status pesanan mereka diubah oleh admin.
 *
 * @param Order  $order       Pesanan yang diperbarui.
 * @param string $newStatus   Status baru: confirmed|shipped|delivered|cancelled.
 * @param string $companyName Nama perusahaan dari CompanyProfile.
 */
class OrderStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param Order  $order
     * @param string $newStatus
     * @param string $companyName
     */
    public function __construct(
        public readonly Order $order,
        public readonly string $newStatus,
        public readonly string $companyName,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->newStatus) {
            'confirmed' => "Pesanan #{$this->order->number} Dikonfirmasi",
            'shipped'   => "Pesanan #{$this->order->number} Sedang Dikirim",
            'delivered' => "Pesanan #{$this->order->number} Telah Selesai",
            'cancelled' => "Pesanan #{$this->order->number} Dibatalkan",
            default     => "Update Status Pesanan #{$this->order->number}",
        };

        return new Envelope(
            subject: "{$this->companyName} - {$subject}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.order.status_changed',
        );
    }
}
