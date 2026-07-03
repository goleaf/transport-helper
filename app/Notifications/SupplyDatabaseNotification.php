<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupplyDatabaseNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public readonly string $type,
        public readonly array $data = [],
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $title = $this->data['title'] ?? str($this->type)->replace('_', ' ')->title()->toString();

        return [
            'type' => $this->type,
            'title' => $title,
            'message' => $this->data['message'] ?? $title,
            'data' => $this->data,
            'url' => $this->data['url'] ?? null,
            'unique_key' => $this->data['unique_key'] ?? null,
            'created_at' => now()->toISOString(),
        ];
    }
}
