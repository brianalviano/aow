<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\ChefStatus;
use App\Mail\ChefStatusUpdatedMail;
use App\Models\{CompanyProfile, Order};
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class ChefStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly Collection $items,
        public readonly ChefStatus $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): ChefStatusUpdatedMail
    {
        $companyName = CompanyProfile::query()->first()?->name ?? 'AOW';

        return new ChefStatusUpdatedMail($this->order, $this->items, $this->newStatus, $companyName);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id'     => $this->order->id,
            'order_number' => $this->order->number,
            'status'       => 'chef_' . $this->newStatus->value,
            'message'      => $this->statusMessage(),
            'url'          => '/orders/' . $this->order->id,
        ];
    }

    private function statusMessage(): string
    {
        $statusLabel = $this->newStatus->label();
        $count = $this->items->count();
        return "Status {$count} item masakan pada pesanan #{$this->order->number} telah diperbarui menjadi: {$statusLabel}.";
    }
}
