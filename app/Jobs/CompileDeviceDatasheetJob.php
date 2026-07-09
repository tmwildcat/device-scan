<?php

namespace App\Jobs;

use App\DeviceScan\Compilers\Inverters\InverterCompiler;
use App\DeviceScan\Compilers\Modules\DTO\ModuleElectricalModelDto;
use App\DeviceScan\Compilers\Modules\ModuleCompiler;
use App\DeviceScan\Storage\DeviceScanArtifactStorage;
use App\LineWatt\Manufacturers\ManufacturerNormalizer;
use App\LineWatt\Activity\ActivityLogger;
use App\LineWatt\Notifications\NotificationManager;
use App\LineWatt\Publishing\PublishingEvent;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CompileDeviceDatasheetJob implements ShouldQueue
{
    use Queueable;

    private const COMPILER_VERSION = 'upload-workflow-v0.1';

    public function __construct(
        public readonly int $deviceDatasheetId,
        public readonly string $selectedDeviceType,
    ) {}

    public function handle(
        ModuleCompiler $moduleCompiler,
        InverterCompiler $inverterCompiler,
        DeviceScanArtifactStorage $artifactStorage,
    ): void {
        $datasheet = DeviceDatasheet::query()->findOrFail($this->deviceDatasheetId);
        $tempPath = $this->localTempPath($datasheet);

        try {
            $datasheet->forceFill([
                'status' => 'compiling',
                'metadata' => [
                    ...($datasheet->metadata ?? []),
                    'compile_started_at' => now()->toIso8601String(),
                ],
            ])->save();

            $this->copyArtifactToLocal($datasheet, $tempPath);

            $datasheet->forceFill([
                'metadata' => [
                    ...($datasheet->metadata ?? []),
                    'compile_selected_device_type' => $this->selectedDeviceType,
                    'compile_temp_source_size_bytes' => is_file($tempPath) ? filesize($tempPath) : null,
                ],
            ])->save();

            if ($this->selectedDeviceType === 'module') {
                $dto = $moduleCompiler->compile($tempPath);
                $this->storeModuleRecords($datasheet, $dto, $artifactStorage);
            } else {
                $dto = $inverterCompiler->compile($tempPath);
                $this->storeInverterRecord($datasheet, $dto, $artifactStorage);
            }

            $datasheet->forceFill([
                'status' => $datasheet->compiledRecords()->where('status', 'review_required')->exists()
                    ? 'review_required'
                    : 'compiled',
                'review_status' => $this->initialReviewStatus($datasheet),
                'compiler_version' => self::COMPILER_VERSION,
                'metadata' => [
                    ...($datasheet->metadata ?? []),
                    'compile_finished_at' => now()->toIso8601String(),
                    'compiled_records_count' => $datasheet->compiledRecords()->count(),
                ],
            ])->save();

            $this->applyInitialWorkflowStatus($datasheet);
            $this->notifyCompileComplete($datasheet);
        } catch (Throwable $exception) {
            $datasheet->forceFill([
                'status' => 'failed',
                'metadata' => [
                    ...($datasheet->metadata ?? []),
                    'compile_failed_at' => now()->toIso8601String(),
                    'compile_error' => [
                        'message' => $exception->getMessage(),
                        'class' => $exception::class,
                    ],
                ],
            ])->save();

            $this->notifyCompileFailed($datasheet, $exception);

            throw $exception;
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    private function storeModuleRecords(DeviceDatasheet $datasheet, object $dto, DeviceScanArtifactStorage $artifactStorage): void
    {
        $models = $dto->electricalStc?->models ?? [];
        $dtoArray = $dto->toArray();
        $detectedManufacturer = $this->normalizeManufacturer($dto->manufacturer ?? null);
        $manufacturer = $this->authoritativeManufacturer($datasheet, $detectedManufacturer);
        $manufacturerMetadata = $this->manufacturerMetadata($datasheet, $detectedManufacturer, $manufacturer);

        DB::transaction(function () use ($datasheet, $models, $dtoArray, $dto, $manufacturer, $manufacturerMetadata, $artifactStorage): void {
            $datasheet->compiledRecords()->delete();

            if ($models === []) {
                $recordJson = [
                    ...$dtoArray,
                    'manufacturer' => $manufacturer,
                    'upload_metadata' => [
                        'compiled_from_datasheet_id' => $datasheet->id,
                        'warning' => 'No STC rating rows were extracted, so one review record was created.',
                        ...$manufacturerMetadata,
                    ],
                ];
                $compiledArtifact = $artifactStorage->storeCompiledJson($recordJson, $this->compiledContext($datasheet, [
                    'manufacturer' => $manufacturer,
                    'product_name' => $datasheet->product_name,
                    'model_name' => 'module-review',
                ]));

                CompiledDeviceRecord::create([
                    ...$this->recordBase($datasheet, $compiledArtifact),
                    'manufacturer' => $manufacturer,
                    'series' => $dto->series ?? $datasheet->series,
                    'family' => $dto->family ?? null,
                    'technology' => $dto->technology ?? null,
                    'display_name' => $datasheet->product_name,
                    'status' => 'review_required',
                    'validation_status' => 'warnings',
                    'metadata' => [
                        'upload_compile' => true,
                        'compile_warning' => 'module_stc_models_not_found',
                        'uploaded_by' => $datasheet->metadata['uploaded_by'] ?? null,
                        ...$manufacturerMetadata,
                    ],
                ]);

                return;
            }

            foreach ($models as $index => $model) {
                /** @var ModuleElectricalModelDto $model */
                $modelArray = $model->toArray();
                $recordJson = $this->moduleRecordJson($dtoArray, $modelArray, $index);
                $recordJson['manufacturer'] = $manufacturer;
                $recordJson['upload_metadata'] = [
                    ...($recordJson['upload_metadata'] ?? []),
                    ...$manufacturerMetadata,
                ];
                $compiledArtifact = $artifactStorage->storeCompiledJson($recordJson, $this->compiledContext($datasheet, [
                    'manufacturer' => $manufacturer,
                    'product_name' => $datasheet->product_name,
                    'model_name' => $modelArray['model_variants'][0] ?? $modelArray['model_series'] ?? $modelArray['display_name'] ?? 'module-rating',
                ]));
                $validationStatus = $this->validationStatus($dtoArray['validation'] ?? null);

                CompiledDeviceRecord::create([
                    ...$this->recordBase($datasheet, $compiledArtifact),
                    'manufacturer' => $manufacturer,
                    'series' => $dto->series ?? $datasheet->series,
                    'family' => $dto->family ?? null,
                    'technology' => $dto->technology ?? null,
                    'model_series' => $modelArray['model_series'] ?? null,
                    'model_name' => $modelArray['model_variants'][0] ?? null,
                    'display_name' => $modelArray['display_name'] ?? null,
                    'power_class_w' => isset($modelArray['power_class_w']) ? (int) round((float) $modelArray['power_class_w']) : null,
                    'status' => $validationStatus === 'errors' ? 'review_required' : 'compiled',
                    'validation_status' => $validationStatus,
                    'metadata' => [
                        'upload_compile' => true,
                        'record_index' => $index,
                        'module_model_variants' => $modelArray['model_variants'] ?? [],
                        'uploaded_by' => $datasheet->metadata['uploaded_by'] ?? null,
                        ...$manufacturerMetadata,
                    ],
                ]);
            }
        });
    }

    private function storeInverterRecord(DeviceDatasheet $datasheet, object $dto, DeviceScanArtifactStorage $artifactStorage): void
    {
        $dtoArray = $dto->toArray();
        $detectedManufacturer = $this->normalizeManufacturer($dto->manufacturer ?? null);
        $manufacturer = $this->authoritativeManufacturer($datasheet, $detectedManufacturer);
        $manufacturerMetadata = $this->manufacturerMetadata($datasheet, $detectedManufacturer, $manufacturer);
        $recordJson = [
            ...$dtoArray,
            'manufacturer' => $manufacturer,
            'upload_metadata' => [
                'compiled_from_datasheet_id' => $datasheet->id,
                ...$manufacturerMetadata,
            ],
        ];
        $compiledArtifact = $artifactStorage->storeCompiledJson($recordJson, $this->compiledContext($datasheet, [
            'manufacturer' => $manufacturer,
            'product_name' => $datasheet->product_name,
            'model_name' => $dto->modelName ?? $dto->modelSeries ?? $dto->displayName ?? 'inverter',
        ]));
        $validationStatus = $this->validationStatus($dtoArray['validation'] ?? null);

        DB::transaction(function () use ($datasheet, $dto, $manufacturer, $manufacturerMetadata, $compiledArtifact, $validationStatus): void {
            $datasheet->compiledRecords()->delete();

            CompiledDeviceRecord::create([
                ...$this->recordBase($datasheet, $compiledArtifact),
                'manufacturer' => $manufacturer,
                'series' => $dto->series ?? $datasheet->series,
                'model_series' => $dto->modelSeries ?? null,
                'model_name' => $dto->modelName ?? null,
                'display_name' => $dto->displayName ?? $datasheet->product_name,
                'power_class_kw' => $dto->powerClassKw ?? null,
                'status' => $validationStatus === 'errors' ? 'review_required' : 'compiled',
                'validation_grade' => $dto->extractionQualityGrade ?? null,
                'validation_score' => $dto->extractionQualityScore ?? null,
                'validation_status' => $validationStatus,
                'metadata' => [
                    'upload_compile' => true,
                    'inverter_device_type' => $dto->deviceType ?? 'unknown',
                    'quality_reasons' => $dto->extractionQualityReasons ?? [],
                    'uploaded_by' => $datasheet->metadata['uploaded_by'] ?? null,
                    ...$manufacturerMetadata,
                ],
            ]);
        });
    }

    private function applyInitialWorkflowStatus(DeviceDatasheet $datasheet): void
    {
        $workspace = $datasheet->metadata['upload_workspace'] ?? null;
        $recordStatus = match ($workspace) {
            'publisher', 'partner' => 'publisher_review',
            'central' => 'librarian_review',
            default => null,
        };

        if (! $recordStatus) {
            return;
        }

        $datasheet->compiledRecords()->update([
            'status' => $recordStatus,
            'review_status' => 'not_reviewed',
        ]);

        $datasheet->forceFill([
            'status' => $recordStatus,
            'review_status' => 'not_reviewed',
        ])->save();
    }

    private function initialReviewStatus(DeviceDatasheet $datasheet): string
    {
        return in_array($datasheet->metadata['upload_workspace'] ?? null, ['publisher', 'partner', 'central'], true)
            ? 'not_reviewed'
            : ($datasheet->review_status ?: 'not_reviewed');
    }

    private function notifyCompileComplete(DeviceDatasheet $datasheet): void
    {
        $activity = app(ActivityLogger::class)->log(PublishingEvent::ENGINEERING_RECORD_COMPILED, null, $datasheet, [
            'device_datasheet_id' => $datasheet->id,
            'compiled_records_count' => $datasheet->compiledRecords()->count(),
        ]);

        foreach ($datasheet->compiledRecords as $record) {
            app(NotificationManager::class)->notifySubmitter(
                $record,
                PublishingEvent::ENGINEERING_RECORD_COMPILED,
                'Compilation complete',
                ($record->display_name ?: $record->manufacturer ?: 'Engineering Record').' is ready for review.',
                $this->reviewUrlForRecord($record),
                $activity,
            );
        }
    }

    private function notifyCompileFailed(DeviceDatasheet $datasheet, Throwable $exception): void
    {
        $activity = app(ActivityLogger::class)->log(PublishingEvent::ENGINEERING_RECORD_COMPILE_FAILED, null, $datasheet, [
            'device_datasheet_id' => $datasheet->id,
            'source_type' => $datasheet->source_type,
            'device_type' => $datasheet->device_type,
            'manufacturer' => $datasheet->manufacturer,
            'error_class' => $exception::class,
        ]);

        app(NotificationManager::class)->notifyLibrarians(
            PublishingEvent::ENGINEERING_RECORD_COMPILE_FAILED,
            'Compile failed',
            ($datasheet->manufacturer ?: 'A datasheet').' failed during compilation and needs operator review.',
            route('admin.library.placeholder', ['section' => 'operations', 'page' => 'compiler-health']),
            $activity,
        );
    }

    private function reviewUrlForRecord(CompiledDeviceRecord $record): ?string
    {
        $workspace = $record->datasheet?->metadata['upload_workspace'] ?? null;
        $key = $record->uuid ?: $record->id;

        return match ($workspace) {
            'publisher' => route('publisher.review.show', ['record' => $key]),
            'partner' => route('partner.submissions.review', ['record' => $key]),
            'central' => route('admin.library.review', ['record' => $key]),
            default => null,
        };
    }

    /**
     * @param array<string,mixed> $dto
     * @param array<string,mixed> $model
     * @return array<string,mixed>
     */
    private function moduleRecordJson(array $dto, array $model, int $index): array
    {
        $dto['models'] = array_values(array_filter([$model['display_name'] ?? null]));
        $dto['electrical_stc']['models'] = [$model];
        $dto['upload_metadata'] = [
            'compiled_from_datasheet_id' => $this->deviceDatasheetId,
            'record_index' => $index,
        ];

        return $dto;
    }

    /**
     * @param array<string,mixed> $artifact
     * @return array<string,mixed>
     */
    private function recordBase(DeviceDatasheet $datasheet, array $artifact): array
    {
        return [
            'device_datasheet_id' => $datasheet->id,
            'source_type' => $datasheet->source_type,
            'tenant_id' => $datasheet->tenant_id,
            'partner_id' => $datasheet->partner_id,
            'device_type' => $datasheet->device_type,
            'compiled_disk' => $artifact['disk'],
            'compiled_path' => $artifact['path'],
            'compiled_sha256' => $artifact['sha256'],
            'compiler_version' => self::COMPILER_VERSION,
        ];
    }

    /**
     * @param array<string,mixed> $overrides
     * @return array<string,mixed>
     */
    private function compiledContext(DeviceDatasheet $datasheet, array $overrides = []): array
    {
        return [
            'source_type' => $datasheet->source_type,
            'device_type' => $datasheet->device_type,
            'manufacturer' => $datasheet->manufacturer,
            'product_name' => $datasheet->product_name,
            'tenant_uuid' => $datasheet->metadata['tenant_uuid'] ?? null,
            'partner_uuid' => $datasheet->metadata['partner_uuid'] ?? null,
            'compiled_uuid' => (string) Str::uuid(),
            ...$overrides,
        ];
    }

    private function copyArtifactToLocal(DeviceDatasheet $datasheet, string $tempPath): void
    {
        $contents = Storage::disk($datasheet->datasheet_disk)->get($datasheet->datasheet_path);

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0750, true);
        }

        file_put_contents($tempPath, $contents);
    }

    private function localTempPath(DeviceDatasheet $datasheet): string
    {
        return storage_path('app/private/device-scan/compile-staging/'.$datasheet->uuid.'.pdf');
    }

    private function validationStatus(?array $validation): ?string
    {
        if ($validation === null) {
            return null;
        }

        $counts = $validation['counts'] ?? $validation['summary']['counts'] ?? null;

        if (is_array($counts)) {
            if (($counts['error'] ?? 0) > 0) {
                return 'errors';
            }

            if (($counts['warning'] ?? 0) > 0) {
                return 'warnings';
            }

            return 'clean';
        }

        $issues = $validation['issues'] ?? [];

        if (is_array($issues)) {
            foreach ($issues as $issue) {
                if (($issue['severity'] ?? null) === 'error') {
                    return 'errors';
                }
            }

            return $issues === [] ? 'clean' : 'warnings';
        }

        return null;
    }

    private function normalizeManufacturer(?string $manufacturer): ?string
    {
        return app(ManufacturerNormalizer::class)->normalize($manufacturer)['name'];
    }

    private function authoritativeManufacturer(DeviceDatasheet $datasheet, ?string $detectedManufacturer): ?string
    {
        if (($datasheet->metadata['upload_workspace'] ?? null) === 'partner') {
            return $datasheet->manufacturer;
        }

        return $detectedManufacturer ?: $datasheet->manufacturer;
    }

    /**
     * @return array<string,mixed>
     */
    private function manufacturerMetadata(DeviceDatasheet $datasheet, ?string $detectedManufacturer, ?string $authoritativeManufacturer): array
    {
        $metadata = [
            'detected_manufacturer' => $detectedManufacturer,
            'detected_manufacturer_confidence' => $detectedManufacturer ? 0.7 : null,
        ];

        if (
            ($datasheet->metadata['upload_workspace'] ?? null) === 'partner'
            && $detectedManufacturer
            && $authoritativeManufacturer
            && str($detectedManufacturer)->lower()->squish()->toString() !== str($authoritativeManufacturer)->lower()->squish()->toString()
        ) {
            $metadata['manufacturer_mismatch_detected'] = true;
            $metadata['extraction_warnings'] = ['manufacturer_mismatch_detected'];
        }

        return $metadata;
    }
}
