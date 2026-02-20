<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLeaveRequest extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public \App\Models\LeaveRequest $leaveRequest)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $sender = $this->leaveRequest->user;
        return (new MailMessage)
            ->subject('Pengajuan Izin Baru - ' . $sender->name)
            ->greeting('Halo Admin,')
            ->line('Terdapat pengajuan izin baru dengan detail sebagai berikut:')
            ->line('Nama: ' . $sender->name)
            ->line('Tipe: ' . $this->leaveRequest->type->label())
            ->line('Mulai: ' . \Carbon\Carbon::parse($this->leaveRequest->start_date)->translatedFormat('d F Y'))
            ->line('Sampai: ' . \Carbon\Carbon::parse($this->leaveRequest->end_date)->translatedFormat('d F Y'))
            ->line('Alasan: ' . $this->leaveRequest->reason)
            ->action('Konfirmasi Pengajuan', route('leaves.manage.index'))
            ->line('Terima kasih menggunakan aplikasi ini.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'Pengajuan Izin Baru',
            'message' => $this->leaveRequest->user->name . ' mengajukan izin ' . $this->leaveRequest->type->label(),
            'url'     => route('leaves.manage.index'),
            'type'    => 'leave_request',
            'data'    => [
                'leave_id' => $this->leaveRequest->id,
                'user_id'  => $this->leaveRequest->user_id,
            ],
        ];
    }
}
