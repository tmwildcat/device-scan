<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'name',
    'client_id',
    'secret_hash',
    'allowed_domains',
    'description',
    'environment',
    'status',
    'scopes',
    'last_used_at',
    'last_used_ip',
    'created_by',
    'revoked_at',
    'metadata',
])]
class InternalApplication extends Model
{
    public const SCOPES = [
        'library.search',
        'library.view_record',
        'library.download_pdf',
        'library.export',
        'library.compare',
        'library.private_upload',
        'library.private_compile',
        'library.storage',
        'library.notifications',
        'mcp.tools',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $application): void {
            $application->uuid ??= (string) Str::uuid();
            $application->client_id ??= self::generateClientId();
        });
    }

    /**
     * @return BelongsTo<User,self>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<InternalApplicationAccessLog>
     */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(InternalApplicationAccessLog::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public static function generateClientId(): string
    {
        return 'lwia_'.Str::lower(Str::random(24));
    }

    public static function generateSecret(): string
    {
        return 'lwias_'.Str::random(48);
    }

    public function setPlainSecret(string $secret): void
    {
        $this->secret_hash = Hash::make($secret);
    }

    public function secretMatches(string $secret): bool
    {
        return Hash::check($secret, $this->secret_hash);
    }

    public function hasScope(?string $scope): bool
    {
        if ($scope === null || $scope === '') {
            return true;
        }

        return in_array($scope, $this->scopes ?? [], true);
    }

    protected function casts(): array
    {
        return [
            'allowed_domains' => 'array',
            'scopes' => 'array',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
