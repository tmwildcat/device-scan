<?php

namespace App\LineWatt\Notifications;

use App\Models\Notification;

class InAppNotificationChannel
{
    public function deliver(Notification $notification): void
    {
        $notification->deliveries()->create([
            'channel' => 'in_app',
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
