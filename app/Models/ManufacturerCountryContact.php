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
    'contact_type',
    'contact_name',
    'email',
    'phone',
    'website',
    'region',
    'status',
    'notes',
    'metadata',
])]
class ManufacturerCountryContact extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $contact): void {
            $contact->uuid ??= (string) Str::uuid();
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
