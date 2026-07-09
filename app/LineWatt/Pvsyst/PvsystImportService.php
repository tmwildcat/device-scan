<?php

namespace App\LineWatt\Pvsyst;

use App\DeviceScan\Compilers\Inverters\DTO\InverterAcOutputDto;
use App\DeviceScan\Compilers\Inverters\DTO\InverterDcInputDto;
use App\DeviceScan\Compilers\Inverters\DTO\InverterDto;
use App\DeviceScan\Compilers\Inverters\DTO\InverterElectricalModelDto;
use App\DeviceScan\Compilers\Inverters\DTO\InverterSourceValueDto;
use App\DeviceScan\Compilers\Inverters\Validation\InverterValidator;
use App\DeviceScan\Compilers\Modules\DTO\ModuleDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleElectricalModelDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleElectricalStcDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleMechanicalDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleOperatingConditionsDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleSourceValueDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleTemperatureCharacteristicsDto;
use App\DeviceScan\Compilers\Modules\Validation\ModuleValidator;
use App\DeviceScan\Storage\DeviceScanArtifactStorage;
use App\LineWatt\Manufacturers\ManufacturerNormalizer;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PvsystImportService
{
    private const COMPILER_VERSION = 'pvsyst-import-v0.1';

    public function __construct(
        private readonly PvsystComponentParser $parser,
        private readonly DeviceScanArtifactStorage $storage,
        private readonly ManufacturerNormalizer $manufacturerNormalizer,
        private readonly ModuleValidator $moduleValidator,
        private readonly InverterValidator $inverterValidator,
    ) {}

    /**
     * @param array<string,mixed> $input
     */
    public function import(User $user, array $input, ?UploadedFile $file = null): PvsystImportResult
    {
        $deviceType = (string) $input['device_type'];
        $inputType = (string) $input['input_type'];
        $template = (string) $input['mapping_template'];
        $manufacturer = $this->manufacturerNormalizer->normalize((string) $input['manufacturer'])['name'];
        $modelName = trim((string) $input['model_name']);
        $series = trim((string) ($input['series'] ?? '')) ?: null;
        $source = $inputType === 'xlsx' && $file
            ? $this->parser->parseXlsx($file, $deviceType, $template)
            : $this->parser->parsePaste((string) ($input['pvsyst_data'] ?? ''), $deviceType, $template);

        $dto = $deviceType === 'module'
            ? $this->moduleDto($manufacturer, $modelName, $series, $source['fields'], $source['warnings'])
            : $this->inverterDto($manufacturer, $modelName, $series, $source['fields'], $source['warnings']);
        $compiledJson = [
            ...$dto->toArray(),
            'manufacturer' => $manufacturer,
            'source_label' => 'PVSyst Import',
            'pvsyst_import' => [
                'input_type' => $inputType,
                'mapping_template' => $template,
                'imported_by' => $user->id,
                'imported_at' => now()->toIso8601String(),
                'warnings' => $source['warnings'],
                'raw_rows' => $source['raw_rows'],
            ],
        ];

        return DB::transaction(function () use ($user, $input, $file, $deviceType, $manufacturer, $modelName, $series, $inputType, $template, $source, $dto, $compiledJson): PvsystImportResult {
            $artifact = $this->storeSourceArtifact($user, $input, $file, $manufacturer, $modelName);
            $datasheet = DeviceDatasheet::create([
                'source_type' => 'pvsyst_import',
                'tenant_id' => $user->id,
                'device_type' => $deviceType,
                'manufacturer' => $manufacturer,
                'series' => $series,
                'product_name' => $modelName,
                'status' => 'compiled',
                'review_status' => 'not_reviewed',
                'datasheet_disk' => $artifact['disk'],
                'datasheet_path' => $artifact['path'],
                'datasheet_original_filename' => $artifact['original_filename'] ?? 'pvsyst-import.txt',
                'datasheet_mime_type' => $artifact['mime_type'] ?? 'text/plain',
                'datasheet_size_bytes' => $artifact['size_bytes'],
                'datasheet_sha256' => $artifact['sha256'],
                'compiler_version' => self::COMPILER_VERSION,
                'pdf_access_mode' => 'user_private',
                'permission_status' => 'restricted',
                'can_public_download' => false,
                'can_public_preview' => false,
                'can_internal_preview' => false,
                'can_private_download' => false,
                'metadata' => [
                    'upload_workspace' => 'my-library',
                    'source_label' => 'PVSyst Import',
                    'source_artifact_type' => 'pvsyst_import',
                    'input_type' => $inputType,
                    'mapping_template' => $template,
                    'imported_by' => $user->id,
                    'imported_at' => now()->toIso8601String(),
                    'tenant_uuid' => 'tenant-'.$user->id,
                    'no_pdf_preview' => true,
                ],
            ]);

            $compiledArtifact = $this->storage->storeCompiledJson($compiledJson, [
                'source_type' => 'pvsyst_import',
                'device_type' => $deviceType,
                'manufacturer' => $manufacturer,
                'product_name' => $modelName,
                'model_name' => $modelName,
                'tenant_uuid' => 'tenant-'.$user->id,
                'compiled_uuid' => (string) Str::uuid(),
            ]);

            $record = CompiledDeviceRecord::create([
                'device_datasheet_id' => $datasheet->id,
                'source_type' => 'pvsyst_import',
                'tenant_id' => $user->id,
                'device_type' => $deviceType,
                'manufacturer' => $manufacturer,
                'series' => $series,
                'model_series' => $series,
                'model_name' => $modelName,
                'display_name' => $modelName,
                'power_class_w' => $deviceType === 'module' ? $this->number(data_get($source['fields'], 'rated_max_power_w.normalized_value')) : null,
                'power_class_kw' => $deviceType === 'inverter' ? $this->kw(data_get($source['fields'], 'rated_ac_power.normalized_value')) : null,
                'status' => 'compiled',
                'review_status' => 'not_reviewed',
                'compiled_disk' => $compiledArtifact['disk'],
                'compiled_path' => $compiledArtifact['path'],
                'compiled_sha256' => $compiledArtifact['sha256'],
                'compiler_version' => self::COMPILER_VERSION,
                'validation_grade' => $deviceType === 'inverter' ? $dto->extractionQualityGrade : null,
                'validation_score' => $deviceType === 'inverter' ? $dto->extractionQualityScore : null,
                'validation_status' => $this->validationStatus($compiledJson['validation'] ?? null),
                'metadata' => [
                    'source_label' => 'PVSyst Import',
                    'source_artifact_type' => 'pvsyst_import',
                    'input_type' => $inputType,
                    'mapping_template' => $template,
                    'imported_by' => $user->id,
                    'imported_at' => now()->toIso8601String(),
                ],
            ]);

            return new PvsystImportResult(
                datasheet: $datasheet->toArray(),
                compiledRecord: $record->toArray(),
                parsedFields: $source['fields'],
                warnings: $source['warnings'],
            );
        });
    }

    private function moduleDto(string $manufacturer, string $modelName, ?string $series, array $fields, array $warnings): ModuleDto
    {
        $model = new ModuleElectricalModelDto(
            modelSeries: $series ?: $modelName,
            modelVariants: [$modelName],
            powerClassW: $this->number($fields['rated_max_power_w']['normalized_value'] ?? null),
            displayName: $modelName,
            ratedMaxPowerW: $this->moduleValue($fields['rated_max_power_w'] ?? null),
            openCircuitVoltageV: $this->moduleValue($fields['open_circuit_voltage_v'] ?? null),
            maximumPowerVoltageV: $this->moduleValue($fields['maximum_power_voltage_v'] ?? null),
            shortCircuitCurrentA: $this->moduleValue($fields['short_circuit_current_a'] ?? null),
            maximumPowerCurrentA: $this->moduleValue($fields['maximum_power_current_a'] ?? null),
            moduleEfficiencyPercent: $this->moduleValue($fields['module_efficiency_percent'] ?? null),
            metadata: ['source' => 'PVSyst Import'],
        );
        $dto = new ModuleDto(
            manufacturer: $manufacturer,
            series: $series,
            family: $series,
            models: [$modelName],
            electricalStc: new ModuleElectricalStcDto([$model], ['source' => 'PVSyst Import']),
            mechanical: new ModuleMechanicalDto(
                lengthMm: $this->moduleValue($fields['length_mm'] ?? null),
                widthMm: $this->moduleValue($fields['width_mm'] ?? null),
                thicknessMm: $this->moduleValue($fields['thickness_mm'] ?? null),
                weightKg: $this->moduleValue($fields['weight_kg'] ?? null),
                metadata: ['source' => 'PVSyst Import'],
            ),
            operatingConditions: new ModuleOperatingConditionsDto(
                maximumSystemVoltage: $this->moduleValue($fields['maximum_system_voltage'] ?? null),
                maximumSeriesFuseRating: $this->moduleValue($fields['maximum_series_fuse_rating'] ?? null),
                metadata: ['source' => 'PVSyst Import'],
            ),
            temperatureCharacteristics: new ModuleTemperatureCharacteristicsDto(
                temperatureCoefficientPmax: $this->moduleValue($fields['temperature_coefficient_pmax'] ?? null),
                temperatureCoefficientVoc: $this->moduleValue($fields['temperature_coefficient_voc'] ?? null),
                temperatureCoefficientIsc: $this->moduleValue($fields['temperature_coefficient_isc'] ?? null),
                metadata: ['source' => 'PVSyst Import'],
            ),
            sourceMetadata: ['source' => 'PVSyst Import'],
            extractionWarnings: $warnings,
        );

        return new ModuleDto(...[
            'manufacturer' => $dto->manufacturer,
            'series' => $dto->series,
            'family' => $dto->family,
            'technology' => $dto->technology,
            'models' => $dto->models,
            'electricalStc' => $dto->electricalStc,
            'electricalVariants' => $dto->electricalVariants,
            'mechanical' => $dto->mechanical,
            'operatingConditions' => $dto->operatingConditions,
            'temperatureCharacteristics' => $dto->temperatureCharacteristics,
            'warranty' => $dto->warranty,
            'packaging' => $dto->packaging,
            'certifications' => $dto->certifications,
            'validation' => $this->moduleValidator->validate($dto),
            'sections' => $dto->sections,
            'sourceMetadata' => $dto->sourceMetadata,
            'extractionWarnings' => $dto->extractionWarnings,
            'warnings' => $dto->warnings,
        ]);
    }

    private function inverterDto(string $manufacturer, string $modelName, ?string $series, array $fields, array $warnings): InverterDto
    {
        $dcFields = [];
        $acFields = [];

        foreach ($fields as $field => $value) {
            if (in_array($field, ['max_dc_voltage', 'startup_voltage', 'rated_dc_voltage', 'mppt_voltage_range', 'mppt_count', 'strings_per_mppt', 'max_input_current', 'max_short_circuit_current'], true)) {
                $dcFields[$field] = $this->inverterValue($value);
            } else {
                $acFields[$field] = $this->inverterValue($value);
            }
        }

        $dto = new InverterDto(
            manufacturer: $manufacturer,
            series: $series,
            modelSeries: $series ?: $modelName,
            modelName: $modelName,
            powerClassKw: $this->kw($fields['rated_ac_power']['normalized_value'] ?? null),
            displayName: $modelName,
            models: [$modelName],
            deviceType: 'string_inverter',
            dcInput: new InverterDcInputDto([new InverterElectricalModelDto($modelName, $dcFields, ['source' => 'PVSyst Import'])]),
            acOutput: new InverterAcOutputDto([new InverterElectricalModelDto($modelName, $acFields, ['source' => 'PVSyst Import'])]),
            extractionWarnings: $warnings,
            sourceMetadata: ['source' => 'PVSyst Import'],
        );
        $validation = $this->inverterValidator->validate($dto);
        $quality = $this->inverterValidator->quality($dto, $validation);

        return new InverterDto(
            manufacturer: $dto->manufacturer,
            series: $dto->series,
            modelSeries: $dto->modelSeries,
            modelName: $dto->modelName,
            powerClassKw: $dto->powerClassKw,
            displayName: $dto->displayName,
            models: $dto->models,
            deviceType: $dto->deviceType,
            unsupportedReason: $dto->unsupportedReason,
            dcInput: $dto->dcInput,
            acOutput: $dto->acOutput,
            ratedPowerConditions: $dto->ratedPowerConditions,
            protection: $dto->protection,
            centralSpecific: $dto->centralSpecific,
            validation: $validation,
            extractionQualityScore: $quality['score'] ?? null,
            extractionQualityGrade: $quality['grade'] ?? null,
            extractionQualityReasons: $quality['reasons'] ?? [],
            sections: $dto->sections,
            extractionWarnings: $dto->extractionWarnings,
            sourceMetadata: $dto->sourceMetadata,
        );
    }

    private function storeSourceArtifact(User $user, array $input, ?UploadedFile $file, string $manufacturer, string $modelName): array
    {
        if ($file) {
            return $this->storage->storeDatasheet($file, [
                'source_type' => 'pvsyst_import',
                'device_type' => $input['device_type'],
                'manufacturer' => $manufacturer,
                'product_name' => $modelName,
                'tenant_uuid' => 'tenant-'.$user->id,
                'datasheet_uuid' => (string) Str::uuid(),
                'extension' => $file->getClientOriginalExtension() ?: 'xlsx',
            ]);
        }

        return $this->storage->storeDatasheet((string) ($input['pvsyst_data'] ?? ''), [
            'source_type' => 'pvsyst_import',
            'device_type' => $input['device_type'],
            'manufacturer' => $manufacturer,
            'product_name' => $modelName,
            'tenant_uuid' => 'tenant-'.$user->id,
            'datasheet_uuid' => (string) Str::uuid(),
            'extension' => 'csv',
        ]);
    }

    private function moduleValue(?array $value): ?ModuleSourceValueDto
    {
        if (! $value) {
            return null;
        }

        return new ModuleSourceValueDto(...[
            'value' => $value['value'] ?? null,
            'unit' => $value['unit'] ?? null,
            'sourceText' => $value['source_text'] ?? null,
            'sourcePage' => null,
            'sourceSection' => $value['source_section'] ?? 'PVSYST_IMPORT',
            'confidence' => $value['confidence'] ?? null,
            'metadata' => $value['metadata'] ?? [],
            'normalizedValue' => $value['normalized_value'] ?? null,
        ]);
    }

    private function inverterValue(?array $value): ?InverterSourceValueDto
    {
        if (! $value) {
            return null;
        }

        return new InverterSourceValueDto(...[
            'value' => $value['value'] ?? null,
            'unit' => $value['unit'] ?? null,
            'sourceText' => $value['source_text'] ?? null,
            'sourcePage' => null,
            'sourceSection' => $value['source_section'] ?? 'PVSYST_IMPORT',
            'confidence' => $value['confidence'] ?? null,
            'metadata' => $value['metadata'] ?? [],
            'normalizedValue' => $value['normalized_value'] ?? null,
        ]);
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function kw(mixed $value): ?float
    {
        $number = $this->number($value);

        return $number === null ? null : ($number > 1000 ? $number / 1000 : $number);
    }

    private function validationStatus(?array $validation): ?string
    {
        $issues = $validation['issues'] ?? [];

        foreach (is_array($issues) ? $issues : [] as $issue) {
            if (($issue['severity'] ?? null) === 'error') {
                return 'errors';
            }
        }

        return $issues === [] ? 'clean' : 'warnings';
    }
}
