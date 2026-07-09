<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'manufacturer_company_id',
    'country',
    'region',
    'availability_status',
    'channel_model',
    'distributor_name',
    'sales_contact',
    'service_contact',
    'notes',
    'metadata',
])]
class ManufacturerDistributionCountry extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $country): void {
            $country->uuid ??= (string) Str::uuid();
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
