<?php

namespace App\DeviceScan\Storage;

use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DeviceScanPromotionService
{
    public function __construct(
        private readonly DeviceScanPathBuilder $paths,
        private readonly DeviceScanArtifactStorage $storage,
    ) {}

    /**
     * @return array{datasheet:DeviceDatasheet,record:CompiledDeviceRecord}
     */
    public function promoteToCentral(CompiledDeviceRecord $sourceRecord): array
    {
        $sourceDatasheet = $sourceRecord->datasheet;

        if (! $sourceDatasheet instanceof DeviceDatasheet) {
            throw new RuntimeException('Compiled record has no source datasheet.');
        }

        if (! in_array($sourceRecord->source_type, ['tenant_private', 'partner_submitted'], true)) {
            throw new RuntimeException('Only tenant or partner records can be promoted to central curated records.');
        }

        return DB::transaction(function () use ($sourceDatasheet, $sourceRecord): array {
            $datasheetUuid = (string) Str::uuid();
            $compiledUuid = (string) Str::uuid();
            $productName = $sourceDatasheet->product_name ?: $sourceRecord->model_series ?: $sourceRecord->display_name ?: 'unknown-product';
            $modelName = $sourceRecord->model_name ?: $sourceRecord->model_series ?: $sourceRecord->display_name ?: 'unknown-model';
            $extension = pathinfo($sourceDatasheet->datasheet_path, PATHINFO_EXTENSION) ?: 'pdf';

            $datasheetPath = $this->paths->buildDatasheetPath([
                'source_type' => 'central_curated',
                'device_type' => $sourceDatasheet->device_type,
                'manufacturer' => $sourceDatasheet->manufacturer,
                'product_name' => $productName,
                'datasheet_uuid' => $datasheetUuid,
                'extension' => $extension,
            ]);
            $compiledPath = $this->paths->buildCompiledJsonPath([
                'source_type' => 'central_curated',
                'device_type' => $sourceRecord->device_type,
                'manufacturer' => $sourceRecord->manufacturer,
                'product_name' => $productName,
                'model_name' => $modelName,
                'compiled_uuid' => $compiledUuid,
            ]);

            $centralDisk = $this->storage->defaultDisk();
            $this->storage->copy($sourceDatasheet->datasheet_path, $datasheetPath, $sourceDatasheet->datasheet_disk, $centralDisk);
            $this->storage->copy($sourceRecord->compiled_path, $compiledPath, $sourceRecord->compiled_disk, $centralDisk);

            $centralDatasheet = DeviceDatasheet::create([
                'uuid' => $datasheetUuid,
                'source_type' => 'central_curated',
                'tenant_id' => null,
                'partner_id' => null,
                'device_type' => $sourceDatasheet->device_type,
                'manufacturer' => $sourceDatasheet->manufacturer,
                'series' => $sourceDatasheet->series,
                'product_name' => $sourceDatasheet->product_name,
                'status' => 'review_required',
                'datasheet_disk' => $centralDisk,
                'datasheet_path' => $datasheetPath,
                'datasheet_original_filename' => $sourceDatasheet->datasheet_original_filename,
                'datasheet_mime_type' => $sourceDatasheet->datasheet_mime_type,
                'datasheet_size_bytes' => $sourceDatasheet->datasheet_size_bytes,
                'datasheet_sha256' => $this->storage->calculateSha256($datasheetPath, $centralDisk),
                'compiler_version' => $sourceDatasheet->compiler_version,
                'metadata' => [
                    ...($sourceDatasheet->metadata ?? []),
                    'promoted_from_datasheet_id' => $sourceDatasheet->id,
                    'promoted_from_source_type' => $sourceDatasheet->source_type,
                ],
            ]);

            $centralRecord = CompiledDeviceRecord::create([
                'uuid' => $compiledUuid,
                'device_datasheet_id' => $centralDatasheet->id,
                'source_type' => 'central_curated',
                'tenant_id' => null,
                'partner_id' => null,
                'device_type' => $sourceRecord->device_type,
                'manufacturer' => $sourceRecord->manufacturer,
                'series' => $sourceRecord->series,
                'family' => $sourceRecord->family,
                'technology' => $sourceRecord->technology,
                'model_series' => $sourceRecord->model_series,
                'model_name' => $sourceRecord->model_name,
                'display_name' => $sourceRecord->display_name,
                'power_class_w' => $sourceRecord->power_class_w,
                'power_class_kw' => $sourceRecord->power_class_kw,
                'status' => 'review_required',
                'compiled_disk' => $centralDisk,
                'compiled_path' => $compiledPath,
                'compiled_sha256' => $this->storage->calculateSha256($compiledPath, $centralDisk),
                'compiler_version' => $sourceRecord->compiler_version,
                'validation_grade' => $sourceRecord->validation_grade,
                'validation_score' => $sourceRecord->validation_score,
                'validation_status' => $sourceRecord->validation_status,
                'metadata' => [
                    ...($sourceRecord->metadata ?? []),
                    'promoted_from_compiled_device_record_id' => $sourceRecord->id,
                    'promoted_from_source_type' => $sourceRecord->source_type,
                ],
            ]);

            return ['datasheet' => $centralDatasheet, 'record' => $centralRecord];
        });
    }
}
