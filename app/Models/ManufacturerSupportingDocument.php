<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid',
    'manufacturer_company_id',
    'device_datasheet_id',
    'compiled_device_record_id',
    'supporting_document_scope',
    'title',
    'category',
    'related_label',
    'model_name',
    'revision',
    'language',
    'status',
    'document_disk',
    'document_path',
    'document_original_filename',
    'document_mime_type',
    'document_size_bytes',
    'document_sha256',
    'metadata',
])]
class ManufacturerSupportingDocument extends Model
{
    public const SCOPE_COMPANY = 'company';

    public const SCOPE_DATASHEET = 'datasheet';

    protected static function booted(): void
    {
        static::creating(function (self $document): void {
            $document->uuid ??= (string) Str::uuid();
        });
    }

    /**
     * @return BelongsTo<ManufacturerCompany,self>
     */
    public function manufacturerCompany(): BelongsTo
    {
        return $this->belongsTo(ManufacturerCompany::class);
    }

    /**
     * @return BelongsTo<DeviceDatasheet,self>
     */
    public function datasheet(): BelongsTo
    {
        return $this->belongsTo(DeviceDatasheet::class, 'device_datasheet_id');
    }

    /**
     * @return BelongsTo<CompiledDeviceRecord,self>
     */
    public function compiledRecord(): BelongsTo
    {
        return $this->belongsTo(CompiledDeviceRecord::class, 'compiled_device_record_id');
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'document_size_bytes' => 'integer',
        ];
    }
}
