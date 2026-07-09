<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'power_search_category_id',
    'label',
    'slug',
    'scope',
    'country',
    'region',
    'subtype',
    'sort_order',
    'is_active',
    'notes',
    'reference_source',
    'last_verified_at',
    'metadata',
])]
class PowerSearchOption extends Model
{
    /**
     * @return BelongsTo<PowerSearchCategory,self>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PowerSearchCategory::class, 'power_search_category_id');
    }

    /**
     * @return HasMany<PowerSearchTagAssignment>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(PowerSearchTagAssignment::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_verified_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
