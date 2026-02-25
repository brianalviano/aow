<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\{CompanyProfile, Customer};
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;

/**
 * Mail sent to new customers with their login credentials.
 */
class CustomerWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public readonly string $companyName;

    /**
     * Create a new message instance.
     *
     * @param Customer $customer The newly created customer.
     * @param string $password The plain-text password.
     */
    public function __construct(
        public readonly Customer $customer,
        public readonly string $password
    ) {
        $this->companyName = CompanyProfile::query()->first()?->name ?? 'AOW';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Selamat Datang di {$this->companyName} - Informasi Akun Anda",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.customer.welcome',
        );
    }
}
