<?php

namespace App\LineWatt\Activity;

use App\Models\Activity;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    /**
     * @param  array<string,mixed>  $metadata
     */
    public function log(string $event, ?User $actor = null, ?Model $subject = null, array $metadata = []): Activity
    {
        return Activity::create([
            'event' => $event,
            'actor_id' => $actor?->id,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'compiled_device_record_id' => $subject instanceof CompiledDeviceRecord ? $subject->id : ($metadata['compiled_device_record_id'] ?? null),
            'device_datasheet_id' => $subject instanceof DeviceDatasheet ? $subject->id : ($metadata['device_datasheet_id'] ?? null),
            'metadata' => $metadata,
        ]);
    }
}
