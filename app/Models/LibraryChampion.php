<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'user_id',
    'name',
    'email',
    'phone',
    'organisation',
    'status',
    'referral_code',
    'commission_type',
    'commission_value',
    'notes',
])]
class LibraryChampion extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $champion): void {
            $champion->uuid ??= (string) Str::uuid();
            $champion->referral_code = Str::upper(trim($champion->referral_code));
        });

        static::saving(function (self $champion): void {
            $champion->referral_code = Str::upper(trim($champion->referral_code));
        });
    }

    protected function casts(): array
    {
        return [
            'commission_value' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User,self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ManufacturerCompany>
     */
    public function manufacturerCompanies(): HasMany
    {
        return $this->hasMany(ManufacturerCompany::class, 'champion_id');
    }
}
