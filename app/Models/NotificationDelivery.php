<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'notification_id',
    'channel',
    'status',
    'sent_at',
    'error',
    'metadata',
])]
class NotificationDelivery extends Model
{
    /**
     * @return BelongsTo<Notification,self>
     */
    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sent_at' => 'datetime',
        ];
    }
}
