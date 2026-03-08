<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\{Chef, Order};
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;

/**
 * Email dikirim ke chef ketika ada pesanan baru yang harus dikonfirmasi.
 */
class ChefOrderAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param Order $order       Pesanan yang ditugaskan.
     * @param Chef  $chef        Chef yang menerima tugas.
     * @param string $companyName Nama perusahaan.
     */
    public function __construct(
        public readonly Order $order,
        public readonly Chef $chef,
        public readonly string $companyName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->companyName} - Pesanan Baru Menunggu Konfirmasi (#{$this->order->number})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.chef.order_assigned',
        );
    }
}
