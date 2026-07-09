<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'device_datasheet_id',
    'source_type',
    'tenant_id',
    'partner_id',
    'device_type',
    'manufacturer',
    'series',
    'family',
    'technology',
    'model_series',
    'model_name',
    'display_name',
    'power_class_w',
    'power_class_kw',
    'status',
    'review_status',
    'compiled_disk',
    'compiled_path',
    'compiled_sha256',
    'compiler_version',
    'validation_grade',
    'validation_score',
    'validation_status',
    'reviewed_by',
    'reviewed_at',
    'metadata',
])]
class CompiledDeviceRecord extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $record): void {
            $record->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<DeviceDatasheet,self>
     */
    public function datasheet(): BelongsTo
    {
        return $this->belongsTo(DeviceDatasheet::class, 'device_datasheet_id');
    }

    /**
     * @return BelongsToMany<PowerSearchOption>
     */
    public function powerSearchOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            PowerSearchOption::class,
            'power_search_tag_assignments',
            'compiled_device_record_id',
            'power_search_option_id'
        )->withPivot(['source', 'notes', 'assigned_by', 'assigned_at'])->withTimestamps();
    }

    /**
     * @return HasMany<ManufacturerSupportingDocument>
     */
    public function supportingDocuments(): HasMany
    {
        return $this->hasMany(ManufacturerSupportingDocument::class);
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'power_class_kw' => 'float',
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<ReviewComment>
     */
    public function reviewComments(): HasMany
    {
        return $this->hasMany(ReviewComment::class);
    }
}
