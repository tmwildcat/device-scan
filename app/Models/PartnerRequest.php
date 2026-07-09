<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'status',
    'company_name',
    'website',
    'country',
    'contact_person',
    'contact_email',
    'official_email_domain',
    'requested_manufacturer_brand',
    'proof_notes',
    'manufacturer_company_id',
    'champion_id',
    'referral_code',
    'referred_at',
    'reviewed_by',
    'reviewed_at',
    'review_comment',
    'metadata',
])]
class PartnerRequest extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $request): void {
            $request->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'reviewed_at' => 'datetime',
            'referred_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ManufacturerCompany,self>
     */
    public function manufacturerCompany(): BelongsTo
    {
        return $this->belongsTo(ManufacturerCompany::class);
    }

    /**
     * @return BelongsTo<LibraryChampion,self>
     */
    public function champion(): BelongsTo
    {
        return $this->belongsTo(LibraryChampion::class, 'champion_id');
    }

    /**
     * @return BelongsTo<User,self>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
