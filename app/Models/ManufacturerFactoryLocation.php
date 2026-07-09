<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'manufacturer_company_id',
    'factory_name',
    'country',
    'state',
    'city',
    'address',
    'product_types',
    'production_capacity',
    'certifications',
    'status',
    'notes',
    'metadata',
])]
class ManufacturerFactoryLocation extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $location): void {
            $location->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<ManufacturerCompany,self>
     */
    public function manufacturerCompany(): BelongsTo
    {
        return $this->belongsTo(ManufacturerCompany::class);
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
