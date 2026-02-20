<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = is_array($this->data) ? $this->data : (array) $this->data;

        $priority = $data['priority'] ?? 'medium';
        $title = $data['title'] ?? 'Notifikasi';
        $message = $data['message'] ?? '';
        $actionUrl = $data['url'] ?? ($data['action_url'] ?? null);

        return [
            'id' => (string) $this->id,
            'type' => 'general',
            'priority' => $priority,
            'title' => $title,
            'message' => $message,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'read_at' => optional($this->read_at)->toDateTimeString(),
            'action_url' => $actionUrl,
        ];
    }
}
