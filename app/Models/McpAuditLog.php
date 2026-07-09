<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'internal_application_id',
    'tool_name',
    'action',
    'status',
    'status_code',
    'input_summary',
    'response_summary',
    'ip',
    'user_agent',
])]
class McpAuditLog extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $log): void {
            $log->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<InternalApplication,self>
     */
    public function internalApplication(): BelongsTo
    {
        return $this->belongsTo(InternalApplication::class);
    }

    protected function casts(): array
    {
        return [
            'input_summary' => 'array',
            'response_summary' => 'array',
        ];
    }
}
