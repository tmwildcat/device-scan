<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'entity_type',
    'entity_id',
    'entity_kind',
    'locale',
    'slug',
    'canonical_path',
    'canonical_url',
    'meta_title',
    'meta_description',
    'meta_keywords',
    'robots',
    'og_title',
    'og_description',
    'og_image',
    'twitter_title',
    'twitter_description',
    'twitter_image',
    'structured_data_enabled',
    'indexable',
    'priority',
    'change_frequency',
    'status',
    'alt_text',
    'image_title',
    'image_caption',
    'metadata',
])]
class SeoMetadata extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $metadata): void {
            $metadata->uuid ??= (string) Str::uuid();
        });

        static::updating(function (self $metadata): void {
            $oldPath = $metadata->getOriginal('canonical_path');

            if (is_string($oldPath) && $oldPath !== '' && $oldPath !== $metadata->canonical_path) {
                SeoRedirect::query()->updateOrCreate(
                    ['source_path' => $oldPath],
                    [
                        'target_path' => $metadata->canonical_path,
                        'status_code' => 301,
                        'reason' => 'Canonical slug changed',
                        'active' => true,
                    ]
                );
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'entity_id' => 'integer',
            'structured_data_enabled' => 'boolean',
            'indexable' => 'boolean',
            'priority' => 'float',
            'metadata' => 'array',
        ];
    }
}
