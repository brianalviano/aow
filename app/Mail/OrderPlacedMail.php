<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\{CompanyProfile, Order};
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;

/**
 * Mail sent to customers when an order is placed.
 */
class OrderPlacedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public readonly string $companyName;

    /**
     * Create a new message instance.
     *
     * @param Order $order The newly created order.
     */
    public function __construct(
        public readonly Order $order
    ) {
        $this->companyName = CompanyProfile::query()->first()?->name ?? 'AOW';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Konfirmasi Pesanan {$this->companyName} - #{$this->order->number}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.order.placed',
        );
    }
}
