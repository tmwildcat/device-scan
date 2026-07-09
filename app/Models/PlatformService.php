<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'name',
    'service_key',
    'service_type',
    'status',
    'environment',
    'description',
    'allowed_internal_application_id',
    'required_scopes',
    'endpoint_url',
    'health_check_url',
    'last_health_check_at',
    'last_status_message',
    'metadata',
])]
class PlatformService extends Model
{
    public const TYPES = [
        'internal_api',
        'mcp_gateway',
        'compiler',
        'storage',
        'email',
        'notification',
        'search_index',
        'scheduled_jobs',
        'website_embed_future',
    ];

    public const STATUSES = ['active', 'paused', 'disabled', 'maintenance'];

    protected static function booted(): void
    {
        static::creating(function (self $service): void {
            $service->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<InternalApplication,self>
     */
    public function allowedInternalApplication(): BelongsTo
    {
        return $this->belongsTo(InternalApplication::class, 'allowed_internal_application_id');
    }

    protected function casts(): array
    {
        return [
            'required_scopes' => 'array',
            'last_health_check_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
