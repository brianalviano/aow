<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;

class PaymentProofUploadedAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $companyName,
        public readonly object $adminUser,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->companyName} - Verifikasi Pembayaran Pesanan #{$this->order->number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.payment.proof_uploaded_admin',
        );
    }
}
