<?php

namespace App\LegalGovernance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalDocument extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'requires_acceptance_default' => 'boolean', 'metadata' => 'array'];
    }

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LegalDocumentVersion::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
