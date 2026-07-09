<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'source_path',
    'target_path',
    'status_code',
    'reason',
    'created_by',
    'active',
    'metadata',
])]
class SeoRedirect extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $redirect): void {
            $redirect->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'status_code' => 'integer',
            'created_by' => 'integer',
            'active' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
