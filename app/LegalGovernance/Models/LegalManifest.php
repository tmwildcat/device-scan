<?php

namespace App\LegalGovernance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class LegalManifest extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['canonical_json' => 'array', 'metadata' => 'array', 'generated_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Legal manifests are immutable.'));
        static::deleting(fn () => throw new LogicException('Legal manifests are immutable.'));
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
