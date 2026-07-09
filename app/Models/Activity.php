<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'event',
    'actor_id',
    'subject_type',
    'subject_id',
    'compiled_device_record_id',
    'device_datasheet_id',
    'metadata',
])]
class Activity extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $activity): void {
            $activity->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<User,self>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * @return BelongsTo<CompiledDeviceRecord,self>
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(CompiledDeviceRecord::class, 'compiled_device_record_id');
    }

    /**
     * @return BelongsTo<DeviceDatasheet,self>
     */
    public function datasheet(): BelongsTo
    {
        return $this->belongsTo(DeviceDatasheet::class, 'device_datasheet_id');
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
