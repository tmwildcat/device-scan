<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $slug
 * @property string $plan_code
 * @property string $subscription_status
 * @property int $max_users
 * @property int|null $champion_id
 * @property string|null $referral_code
 * @property array<string,mixed>|null $metadata
 */
#[Fillable([
    'uuid',
    'name',
    'slug',
    'plan_code',
    'subscription_status',
    'max_users',
    'champion_id',
    'referral_code',
    'referred_at',
    'metadata',
])]
class ManufacturerCompany extends Model
{
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'referred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<LibraryChampion,self>
     */
    public function champion(): BelongsTo
    {
        return $this->belongsTo(LibraryChampion::class, 'champion_id');
    }

    /**
     * @return HasMany<User>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<ManufacturerSupportingDocument>
     */
    public function supportingDocuments(): HasMany
    {
        return $this->hasMany(ManufacturerSupportingDocument::class);
    }

    /**
     * @return HasMany<ManufacturerFactoryLocation>
     */
    public function factoryLocations(): HasMany
    {
        return $this->hasMany(ManufacturerFactoryLocation::class);
    }

    /**
     * @return HasMany<ManufacturerDistributionCountry>
     */
    public function distributionCountries(): HasMany
    {
        return $this->hasMany(ManufacturerDistributionCountry::class);
    }

    /**
     * @return HasMany<ManufacturerCountryContact>
     */
    public function countryContacts(): HasMany
    {
        return $this->hasMany(ManufacturerCountryContact::class);
    }

    protected function planLabel(): Attribute
    {
        return Attribute::get(fn (): string => match ($this->plan_code) {
            'enterprise' => 'Enterprise',
            default => 'Pro',
        });
    }

    public static function slugFor(string $name): string
    {
        return Str::slug($name);
    }
}
