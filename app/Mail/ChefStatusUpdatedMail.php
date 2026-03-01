<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\ChefStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ChefStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly Collection $items,
        public readonly ChefStatus $newStatus,
        public readonly string $companyName,
    ) {}

    public function envelope(): Envelope
    {
        $statusLabel = $this->newStatus->label();
        return new Envelope(
            subject: "{$this->companyName} - Update Item Pesanan #{$this->order->number} ({$statusLabel})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.order.chef_status_updated',
        );
    }
}
