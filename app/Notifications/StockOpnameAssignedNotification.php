<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\StockOpname;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class StockOpnameAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly StockOpname $opname) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('stock-opnames.index');
        return (new MailMessage())
            ->subject('Penugasan Stok Opname: ' . (string) ($this->opname->number ?? ''))
            ->greeting('Halo ' . (string) ($notifiable->name ?? ''))
            ->line('Anda ditugaskan untuk melakukan stok opname.')
            ->line('Nomor: ' . (string) ($this->opname->number ?? ''))
            ->action('Buka Stok Opname', $url);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Penugasan Stok Opname',
            'message' => 'Anda ditugaskan untuk SO nomor ' . (string) ($this->opname->number ?? ''),
            'url' => route('stock-opnames.index'),
            'type' => 'stock_opname_assigned',
            'priority' => 'medium',
            'data' => [
                'stock_opname_id' => (string) $this->opname->getKey(),
                'number' => (string) ($this->opname->number ?? ''),
            ],
        ];
    }
}
