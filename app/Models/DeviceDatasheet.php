<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'source_type',
    'tenant_id',
    'partner_id',
    'device_type',
    'manufacturer',
    'series',
    'product_name',
    'status',
    'review_status',
    'datasheet_disk',
    'datasheet_path',
    'datasheet_original_filename',
    'datasheet_mime_type',
    'datasheet_size_bytes',
    'datasheet_sha256',
    'compiler_version',
    'pdf_access_mode',
    'source_url',
    'source_domain',
    'permission_status',
    'permission_notes',
    'attribution_text',
    'can_public_download',
    'can_public_preview',
    'can_internal_preview',
    'can_private_download',
    'reviewed_by',
    'reviewed_at',
    'metadata',
])]
class DeviceDatasheet extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $datasheet): void {
            $datasheet->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return HasMany<CompiledDeviceRecord>
     */
    public function compiledRecords(): HasMany
    {
        return $this->hasMany(CompiledDeviceRecord::class);
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
            'reviewed_at' => 'datetime',
            'can_public_download' => 'boolean',
            'can_public_preview' => 'boolean',
            'can_internal_preview' => 'boolean',
            'can_private_download' => 'boolean',
        ];
    }

    protected function datasheetSizeBytes(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value === null ? null : (int) $value,
        );
    }
}
