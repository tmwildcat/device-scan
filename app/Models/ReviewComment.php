<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'compiled_device_record_id',
    'actor_id',
    'action',
    'comment',
    'previous_status',
    'new_status',
    'metadata',
])]
class ReviewComment extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<CompiledDeviceRecord,self>
     */
    public function record(): BelongsTo
    {
        return $this->belongsTo(CompiledDeviceRecord::class, 'compiled_device_record_id');
    }

    /**
     * @return BelongsTo<User,self>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
