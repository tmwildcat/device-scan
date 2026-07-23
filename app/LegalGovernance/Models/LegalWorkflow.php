<?php

namespace App\LegalGovernance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegalWorkflow extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['configuration' => 'array', 'effective_from' => 'immutable_datetime', 'effective_until' => 'immutable_datetime'];
    }

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(LegalWorkflowRequirement::class)->orderBy('sequence');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
