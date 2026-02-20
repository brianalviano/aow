<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeaveRequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public \App\Models\LeaveRequest $leaveRequest)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sender = $notifiable;
        return (new MailMessage)
            ->subject('Permohonan Izin Ditolak')
            ->greeting('Halo ' . ($sender->name ?? ''))
            ->line('Permohonan izin Anda ditolak.')
            ->line('Tipe: ' . $this->leaveRequest->type->label())
            ->line('Mulai: ' . \Carbon\Carbon::parse($this->leaveRequest->start_date)->translatedFormat('d F Y'))
            ->line('Sampai: ' . \Carbon\Carbon::parse($this->leaveRequest->end_date)->translatedFormat('d F Y'))
            ->line('Alasan: ' . $this->leaveRequest->reason)
            ->action('Lihat Permohonan Izin', route('leaves.index'))
            ->line('Silakan ajukan ulang jika diperlukan.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Permohonan Izin Ditolak',
            'message' => 'Permohonan izin Anda ditolak: ' . $this->leaveRequest->type->label(),
            'url' => route('leaves.index'),
            'type' => 'leave_request',
            'data' => [
                'leave_id' => $this->leaveRequest->id,
                'user_id' => $this->leaveRequest->user_id,
                'status' => method_exists($this->leaveRequest->status, 'value') ? $this->leaveRequest->status->value : $this->leaveRequest->status,
            ],
        ];
    }
}

