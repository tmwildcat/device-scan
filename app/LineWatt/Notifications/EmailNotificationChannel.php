<?php

namespace App\LineWatt\Notifications;

use App\Models\Notification;

class EmailNotificationChannel
{
    public function queue(Notification $notification): void
    {
        $notification->deliveries()->create([
            'channel' => 'email',
            'status' => 'pending',
            'metadata' => [
                'placeholder' => true,
                'note' => 'Queued email delivery placeholder. Real mailable wiring comes later.',
            ],
        ]);
    }
}
