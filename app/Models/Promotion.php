<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'code',
    'title',
    'description',
    'discount_type',
    'discount_value',
    'applies_to_plan',
    'starts_at',
    'ends_at',
    'max_redemptions',
    'redemption_count',
    'status',
    'paddle_coupon_id',
    'metadata',
])]
class Promotion extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $promotion): void {
            $promotion->uuid ??= (string) Str::uuid();
            $promotion->code = Str::upper(trim($promotion->code));
        });

        static::saving(function (self $promotion): void {
            $promotion->code = Str::upper(trim($promotion->code));
        });
    }

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
