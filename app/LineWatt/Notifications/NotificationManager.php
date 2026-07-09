<?php

namespace App\LineWatt\Notifications;

use App\LineWatt\Access\LineWattRole;
use App\Models\Activity;
use App\Models\CompiledDeviceRecord;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationManager
{
    public function __construct(
        private readonly InAppNotificationChannel $inApp,
        private readonly EmailNotificationChannel $email,
    ) {}

    /**
     * @param  iterable<User>  $users
     * @param  array<string,mixed>  $metadata
     */
    public function notify(iterable $users, string $type, string $title, ?string $body = null, ?string $actionUrl = null, ?Activity $activity = null, array $metadata = [], bool $email = false): void
    {
        foreach ($users as $user) {
            $notification = Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'action_url' => $actionUrl,
                'activity_id' => $activity?->id,
                'metadata' => $metadata,
            ]);

            $this->inApp->deliver($notification);

            if ($email) {
                $this->email->queue($notification);
            }
        }
    }

    public function notifyLibrarians(string $type, string $title, ?string $body = null, ?string $actionUrl = null, ?Activity $activity = null, bool $email = false): void
    {
        $this->notify($this->librarians(), $type, $title, $body, $actionUrl, $activity, [], $email);
    }

    public function notifySubmitter(CompiledDeviceRecord $record, string $type, string $title, ?string $body = null, ?string $actionUrl = null, ?Activity $activity = null, bool $email = false): void
    {
        $submitter = $this->submitter($record);

        if ($submitter) {
            $this->notify([$submitter], $type, $title, $body, $actionUrl, $activity, [
                'compiled_device_record_id' => $record->id,
            ], $email);
        }
    }

    /**
     * @return Collection<int,User>
     */
    private function librarians(): Collection
    {
        return User::query()
            ->whereIn('role', [LineWattRole::LIBRARIAN, LineWattRole::ADMIN, LineWattRole::SUPER_ADMIN])
            ->get();
    }

    private function submitter(CompiledDeviceRecord $record): ?User
    {
        $submitterId = $record->metadata['submitted_by']
            ?? $record->metadata['uploaded_by']
            ?? $record->tenant_id
            ?? $record->partner_id
            ?? null;

        return $submitterId ? User::query()->find($submitterId) : null;
    }
}
