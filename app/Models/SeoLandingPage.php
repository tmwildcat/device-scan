<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'kind',
    'title',
    'slug',
    'description',
    'taxonomy_type',
    'taxonomy_value',
    'status',
    'metadata',
])]
class SeoLandingPage extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $page): void {
            $page->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
