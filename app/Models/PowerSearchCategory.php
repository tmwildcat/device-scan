<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'scope',
    'sort_order',
    'is_active',
    'metadata',
])]
class PowerSearchCategory extends Model
{
    /**
     * @return HasMany<PowerSearchOption>
     */
    public function options(): HasMany
    {
        return $this->hasMany(PowerSearchOption::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }
}
