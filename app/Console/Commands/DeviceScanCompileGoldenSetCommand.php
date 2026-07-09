<?php

namespace App\Console\Commands;

use App\DeviceScan\Compilers\Inverters\InverterCompiler;
use App\DeviceScan\Compilers\Modules\DTO\ModuleElectricalModelDto;
use App\DeviceScan\Compilers\Modules\ModuleCompiler;
use App\DeviceScan\Storage\DeviceScanArtifactStorage;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

#[Signature('device-scan:compile-golden-set {--modules : Compile module corpus PDFs} {--string-inverters : Compile string/hybrid inverter corpus PDFs} {--force : Replace existing golden rows for the same corpus PDF} {--dry-run : Compile and report without storing artifacts}')]
#[Description('Compile the librarian golden set and store central curated PDF/JSON artifacts.')]
class DeviceScanCompileGoldenSetCommand extends Command
{
    private const COMPILER_VERSION = 'golden-set-2026-06-30';

    public function __construct(
        private readonly ModuleCompiler $moduleCompiler,
        private readonly InverterCompiler $inverterCompiler,
        private readonly DeviceScanArtifactStorage $storage,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        ini_set('memory_limit', '-1');
        $this->assertStorageIsConfigured();

        $compileModules = (bool) $this->option('modules');
        $compileInverters = (bool) $this->option('string-inverters');

        if (! $compileModules && ! $compileInverters) {
            $compileModules = true;
            $compileInverters = true;
        }

        $summary = [
            'module_pdfs' => 0,
            'module_records' => 0,
            'inverter_pdfs' => 0,
            'inverter_records' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        if ($compileModules) {
            foreach ($this->modulePdfs() as $pdfPath) {
                $result = $this->compileModulePdf($pdfPath);
                $summary['module_pdfs'] += $result['pdfs'];
                $summary['module_records'] += $result['records'];
                $summary['skipped'] += $result['skipped'];
                $summary['failed'] += $result['failed'];
            }
        }

        if ($compileInverters) {
            foreach ($this->stringInverterPdfs() as $pdfPath) {
                $result = $this->compileInverterPdf($pdfPath);
                $summary['inverter_pdfs'] += $result['pdfs'];
                $summary['inverter_records'] += $result['records'];
                $summary['skipped'] += $result['skipped'];
                $summary['failed'] += $result['failed'];
            }
        }

        $this->newLine();
        $this->info('Golden set compile complete.');
        $this->table(
            ['Module PDFs', 'Module Records', 'Inverter PDFs', 'Inverter Records', 'Skipped', 'Failed'],
            [[
                $summary['module_pdfs'],
                $summary['module_records'],
                $summary['inverter_pdfs'],
                $summary['inverter_records'],
                $summary['skipped'],
                $summary['failed'],
            ]]
        );

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return array{pdfs:int,records:int,skipped:int,failed:int}
     */
    private function compileModulePdf(string $pdfPath): array
    {
        $relativePath = $this->relativePath($pdfPath);
        $this->line("Compiling module: {$relativePath}");

        try {
            $dto = $this->moduleCompiler->compile($pdfPath);
            $models = $dto->electricalStc?->models ?? [];

            if ($models === []) {
                $this->warn('  skipped: no STC ratings extracted');

                return ['pdfs' => 0, 'records' => 0, 'skipped' => 1, 'failed' => 0];
            }

            if ($this->option('dry-run')) {
                $this->info('  dry-run: '.count($models).' records');

                return ['pdfs' => 1, 'records' => count($models), 'skipped' => 0, 'failed' => 0];
            }

            $records = DB::transaction(function () use ($pdfPath, $relativePath, $dto, $models): int {
                $manufacturer = $this->normalizeManufacturer($dto->manufacturer);
                $this->deleteExistingGoldenRows($relativePath);
                $datasheetArtifact = $this->storage->storeDatasheet($pdfPath, [
                    'source_type' => 'central_curated',
                    'device_type' => 'module',
                    'manufacturer' => $manufacturer,
                    'product_name' => $dto->series ?: pathinfo($pdfPath, PATHINFO_FILENAME),
                    'datasheet_uuid' => (string) Str::uuid(),
                    'extension' => 'pdf',
                ]);

                $datasheet = DeviceDatasheet::create([
                    'source_type' => 'central_curated',
                    'device_type' => 'module',
                    'manufacturer' => $manufacturer,
                    'series' => $dto->series,
                    'product_name' => $dto->series ?: pathinfo($pdfPath, PATHINFO_FILENAME),
                    'status' => 'compiled',
                    'datasheet_disk' => $datasheetArtifact['disk'],
                    'datasheet_path' => $datasheetArtifact['path'],
                    'datasheet_original_filename' => basename($pdfPath),
                    'datasheet_mime_type' => $datasheetArtifact['mime_type'] ?? 'application/pdf',
                    'datasheet_size_bytes' => $datasheetArtifact['size_bytes'],
                    'datasheet_sha256' => $datasheetArtifact['sha256'],
                    'compiler_version' => self::COMPILER_VERSION,
                    'reviewed_by' => $this->librarianUser()?->id,
                    'reviewed_at' => now(),
                    'metadata' => $this->datasheetMetadata($relativePath, 'module'),
                ]);

                $created = 0;

                foreach ($models as $index => $model) {
                    $recordJson = $this->moduleRecordJson($dto->toArray(), $model, $relativePath, $index);
                    $recordJson['manufacturer'] = $manufacturer;
                    $modelArray = $model->toArray();
                    $compiledArtifact = $this->storage->storeCompiledJson($recordJson, [
                        'source_type' => 'central_curated',
                        'device_type' => 'module',
                        'manufacturer' => $manufacturer,
                        'product_name' => $datasheet->product_name,
                        'model_name' => $modelArray['model_variants'][0] ?? $modelArray['model_series'] ?? $modelArray['display_name'] ?? 'module-rating',
                        'compiled_uuid' => (string) Str::uuid(),
                    ]);

                    CompiledDeviceRecord::create([
                        'device_datasheet_id' => $datasheet->id,
                        'source_type' => 'central_curated',
                        'device_type' => 'module',
                        'manufacturer' => $manufacturer,
                        'series' => $dto->series,
                        'family' => $dto->family,
                        'technology' => $dto->technology,
                        'model_series' => $modelArray['model_series'] ?? null,
                        'model_name' => $modelArray['model_variants'][0] ?? null,
                        'display_name' => $modelArray['display_name'] ?? null,
                        'power_class_w' => isset($modelArray['power_class_w']) ? (int) round((float) $modelArray['power_class_w']) : null,
                        'status' => 'compiled',
                        'compiled_disk' => $compiledArtifact['disk'],
                        'compiled_path' => $compiledArtifact['path'],
                        'compiled_sha256' => $compiledArtifact['sha256'],
                        'compiler_version' => self::COMPILER_VERSION,
                        'validation_status' => $this->validationStatus($dto->toArray()['validation'] ?? null),
                        'reviewed_by' => $this->librarianUser()?->id,
                        'reviewed_at' => now(),
                        'metadata' => [
                            'golden_set' => true,
                            'golden_source_path' => $relativePath,
                            'golden_record_index' => $index,
                            'module_model_variants' => $modelArray['model_variants'] ?? [],
                        ],
                    ]);

                    $created++;
                }

                return $created;
            });

            $this->info("  stored {$records} module records");

            return ['pdfs' => 1, 'records' => $records, 'skipped' => 0, 'failed' => 0];
        } catch (Throwable $exception) {
            $this->error('  failed: '.$exception->getMessage());

            return ['pdfs' => 0, 'records' => 0, 'skipped' => 0, 'failed' => 1];
        }
    }

    /**
     * @return array{pdfs:int,records:int,skipped:int,failed:int}
     */
    private function compileInverterPdf(string $pdfPath): array
    {
        $relativePath = $this->relativePath($pdfPath);
        $this->line("Compiling inverter: {$relativePath}");

        try {
            $dto = $this->inverterCompiler->compile($pdfPath);

            if (! in_array($dto->deviceType, ['string_inverter', 'hybrid_inverter'], true)) {
                $this->warn("  skipped: {$dto->deviceType}");

                return ['pdfs' => 0, 'records' => 0, 'skipped' => 1, 'failed' => 0];
            }

            if ($dto->unsupportedReason !== null) {
                $this->warn("  skipped: {$dto->unsupportedReason}");

                return ['pdfs' => 0, 'records' => 0, 'skipped' => 1, 'failed' => 0];
            }

            if ($this->option('dry-run')) {
                $this->info('  dry-run: 1 record');

                return ['pdfs' => 1, 'records' => 1, 'skipped' => 0, 'failed' => 0];
            }

            DB::transaction(function () use ($pdfPath, $relativePath, $dto): void {
                $manufacturer = $this->normalizeManufacturer($dto->manufacturer);
                $this->deleteExistingGoldenRows($relativePath);
                $datasheetArtifact = $this->storage->storeDatasheet($pdfPath, [
                    'source_type' => 'central_curated',
                    'device_type' => 'inverter',
                    'manufacturer' => $manufacturer,
                    'product_name' => $dto->series ?: $dto->displayName ?: pathinfo($pdfPath, PATHINFO_FILENAME),
                    'datasheet_uuid' => (string) Str::uuid(),
                    'extension' => 'pdf',
                ]);

                $datasheet = DeviceDatasheet::create([
                    'source_type' => 'central_curated',
                    'device_type' => 'inverter',
                    'manufacturer' => $manufacturer,
                    'series' => $dto->series,
                    'product_name' => $dto->displayName ?: $dto->series ?: pathinfo($pdfPath, PATHINFO_FILENAME),
                    'status' => 'compiled',
                    'datasheet_disk' => $datasheetArtifact['disk'],
                    'datasheet_path' => $datasheetArtifact['path'],
                    'datasheet_original_filename' => basename($pdfPath),
                    'datasheet_mime_type' => $datasheetArtifact['mime_type'] ?? 'application/pdf',
                    'datasheet_size_bytes' => $datasheetArtifact['size_bytes'],
                    'datasheet_sha256' => $datasheetArtifact['sha256'],
                    'compiler_version' => self::COMPILER_VERSION,
                    'reviewed_by' => $this->librarianUser()?->id,
                    'reviewed_at' => now(),
                    'metadata' => $this->datasheetMetadata($relativePath, $dto->deviceType ?? 'inverter'),
                ]);

                $recordJson = [
                    ...$dto->toArray(),
                    'manufacturer' => $manufacturer,
                    'golden_metadata' => [
                        'golden_set' => true,
                        'golden_source_path' => $relativePath,
                        'compiled_by_role' => 'librarian',
                    ],
                ];
                $compiledArtifact = $this->storage->storeCompiledJson($recordJson, [
                    'source_type' => 'central_curated',
                    'device_type' => 'inverter',
                    'manufacturer' => $manufacturer,
                    'product_name' => $datasheet->product_name,
                    'model_name' => $dto->modelName ?: $dto->modelSeries ?: $dto->displayName ?: 'inverter',
                    'compiled_uuid' => (string) Str::uuid(),
                ]);

                CompiledDeviceRecord::create([
                    'device_datasheet_id' => $datasheet->id,
                    'source_type' => 'central_curated',
                    'device_type' => 'inverter',
                    'manufacturer' => $manufacturer,
                    'series' => $dto->series,
                    'model_series' => $dto->modelSeries,
                    'model_name' => $dto->modelName,
                    'display_name' => $dto->displayName,
                    'power_class_kw' => $dto->powerClassKw,
                    'status' => 'compiled',
                    'compiled_disk' => $compiledArtifact['disk'],
                    'compiled_path' => $compiledArtifact['path'],
                    'compiled_sha256' => $compiledArtifact['sha256'],
                    'compiler_version' => self::COMPILER_VERSION,
                    'validation_grade' => $dto->extractionQualityGrade,
                    'validation_score' => $dto->extractionQualityScore,
                    'validation_status' => $this->validationStatus($dto->toArray()['validation'] ?? null),
                    'reviewed_by' => $this->librarianUser()?->id,
                    'reviewed_at' => now(),
                    'metadata' => [
                        'golden_set' => true,
                        'golden_source_path' => $relativePath,
                        'inverter_device_type' => $dto->deviceType,
                        'quality_reasons' => $dto->extractionQualityReasons,
                    ],
                ]);
            });

            $this->info('  stored 1 inverter record');

            return ['pdfs' => 1, 'records' => 1, 'skipped' => 0, 'failed' => 0];
        } catch (Throwable $exception) {
            $this->error('  failed: '.$exception->getMessage());

            return ['pdfs' => 0, 'records' => 0, 'skipped' => 0, 'failed' => 1];
        }
    }

    /**
     * @return string[]
     */
    private function modulePdfs(): array
    {
        return $this->canonicalPdfs(storage_path('app/private/device-scan/corpus/modules'));
    }

    /**
     * @return string[]
     */
    private function stringInverterPdfs(): array
    {
        return array_values(array_filter(
            $this->canonicalPdfs(storage_path('app/private/device-scan/corpus/inverters')),
            fn (string $path): bool => ! str_contains($path, DIRECTORY_SEPARATOR.'central'.DIRECTORY_SEPARATOR)
                && ! str_contains(mb_strtolower($path), 'backup_box')
                && ! str_contains(mb_strtolower($path), 'syn50')
                && ! str_contains(mb_strtolower($path), 'waaree')
        ));
    }

    /**
     * @return string[]
     */
    private function canonicalPdfs(string $root): array
    {
        $paths = glob($root.'/**/*.pdf') ?: [];
        $paths = [
            ...$paths,
            ...(glob($root.'/*.pdf') ?: []),
        ];
        $byBasename = [];

        foreach ($paths as $path) {
            $basename = basename($path);
            $existing = $byBasename[$basename] ?? null;

            if ($existing === null || dirname($existing) === $root) {
                $byBasename[$basename] = $path;
            }
        }

        sort($byBasename);

        return array_values($byBasename);
    }

    private function deleteExistingGoldenRows(string $relativePath): void
    {
        $existingDatasheets = DeviceDatasheet::query()
            ->where('source_type', 'central_curated')
            ->where('metadata->golden_source_path', $relativePath)
            ->get();

        if ($existingDatasheets->isEmpty()) {
            return;
        }

        if (! $this->option('force')) {
            throw new \RuntimeException("Golden row already exists for [{$relativePath}]. Use --force to replace it.");
        }

        foreach ($existingDatasheets as $datasheet) {
            foreach ($datasheet->compiledRecords as $record) {
                $this->storage->delete($record->compiled_path, $record->compiled_disk);
            }

            $this->storage->delete($datasheet->datasheet_path, $datasheet->datasheet_disk);
            $datasheet->delete();
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function moduleRecordJson(array $dto, ModuleElectricalModelDto $model, string $relativePath, int $index): array
    {
        $modelArray = $model->toArray();
        $dto['models'] = array_values(array_filter([$modelArray['display_name'] ?? null]));
        $dto['electrical_stc']['models'] = [$modelArray];
        $dto['golden_metadata'] = [
            'golden_set' => true,
            'golden_source_path' => $relativePath,
            'golden_record_index' => $index,
            'compiled_by_role' => 'librarian',
        ];

        return $dto;
    }

    /**
     * @return array<string,mixed>
     */
    private function datasheetMetadata(string $relativePath, string $deviceType): array
    {
        return [
            'golden_set' => true,
            'golden_source_path' => $relativePath,
            'golden_device_type' => $deviceType,
            'compiled_by_role' => 'librarian',
        ];
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

    private function librarianUser(): ?User
    {
        return User::query()->where('email', 'librarian@linewatt.test')->first();
    }

    private function relativePath(string $path): string
    {
        return ltrim(str_replace(base_path().DIRECTORY_SEPARATOR, '', $path), DIRECTORY_SEPARATOR);
    }

    private function normalizeManufacturer(?string $manufacturer): ?string
    {
        $normalized = trim((string) $manufacturer);
        $key = Str::lower(preg_replace('/[^a-z0-9]+/i', '', $normalized) ?? '');

        return match ($key) {
            'jasolar' => 'JA Solar',
            'longi' => 'LONGi',
            'rec' => 'REC',
            'sma' => 'SMA',
            default => $normalized !== '' ? $normalized : null,
        };
    }

    private function assertStorageIsConfigured(): void
    {
        if ($this->option('dry-run')) {
            return;
        }

        $disk = $this->storage->defaultDisk();
        $config = config("filesystems.disks.{$disk}", []);

        if (! is_array($config) || $config === []) {
            throw new \RuntimeException("Storage disk [{$disk}] is not configured.");
        }

        if (($config['driver'] ?? null) === 's3') {
            foreach (['key', 'secret', 'region', 'bucket', 'endpoint'] as $key) {
                if (! is_string($config[$key] ?? null) || trim((string) $config[$key]) === '') {
                    throw new \RuntimeException("S3 storage disk [{$disk}] is missing required config [{$key}].");
                }
            }
        }

        Storage::disk($disk);
    }
}
