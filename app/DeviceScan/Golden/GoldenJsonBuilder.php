<?php

declare(strict_types=1);

namespace App\DeviceScan\Golden;

use App\DeviceScan\Compilers\Inverters\DTO\InverterDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleElectricalModelDto;
use Illuminate\Support\Str;

final class GoldenJsonBuilder
{
    public const SCHEMA_VERSION = 'linewatt.golden.v0.1';

    public const MODULE_COMPILER_VERSION = 'module-compiler-v0.2';

    public const INVERTER_COMPILER_VERSION = 'inverter-compiler-v0.4';

    /**
     * @return list<array<string,mixed>>
     */
    public function moduleRecords(ModuleDto $dto, string $pdfPath): array
    {
        $models = $dto->electricalStc?->models ?? [];

        if ($models === []) {
            return [$this->moduleRecord($dto, null, $pdfPath, 0)];
        }

        return array_values(array_map(
            fn (ModuleElectricalModelDto $model, int $index): array => $this->moduleRecord($dto, $model, $pdfPath, $index),
            $models,
            array_keys($models),
        ));
    }

    /**
     * @return array<string,mixed>
     */
    public function inverterRecord(InverterDto $dto, string $pdfPath): array
    {
        $compiled = $dto->toArray();

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'compiler_version' => self::INVERTER_COMPILER_VERSION,
            'record_type' => 'inverter_engineering_record',
            'device_type' => 'inverter',
            'identity' => [
                'manufacturer' => $dto->manufacturer,
                'series' => $dto->series,
                'family' => null,
                'model_series' => $dto->modelSeries,
                'model_name' => $dto->modelName,
                'display_name' => $dto->displayName ?? $dto->modelName ?? $dto->modelSeries,
                'power_class_kw' => $dto->powerClassKw,
                'technology' => null,
                'inverter_device_type' => $dto->deviceType,
            ],
            'source_datasheet' => $this->sourceDatasheet($pdfPath, $compiled['source_metadata'] ?? []),
            'manufacturer' => $dto->manufacturer,
            'model' => [
                'series' => $dto->series,
                'model_series' => $dto->modelSeries,
                'model_name' => $dto->modelName,
                'display_name' => $dto->displayName ?? $dto->modelName ?? $dto->modelSeries,
                'power_class_kw' => $dto->powerClassKw,
            ],
            'engineering' => [
                'dc_input' => $compiled['dc_input'] ?? null,
                'ac_output' => $compiled['ac_output'] ?? null,
                'rated_power_conditions' => $compiled['rated_power_conditions'] ?? [],
                'protection' => $compiled['protection'] ?? null,
                'central_specific' => $compiled['central_specific'] ?? null,
                'storage_hybrid' => [
                    'device_type' => $dto->deviceType,
                    'unsupported_reason' => $dto->unsupportedReason,
                ],
            ],
            'source_provenance' => $this->sourceProvenance($compiled),
            'validation_issues' => $this->validationIssues($compiled['validation'] ?? null),
            'validation' => $compiled['validation'] ?? null,
            'extraction_warnings' => $compiled['extraction_warnings'] ?? [],
            'sections' => $compiled['sections'] ?? [],
            'quality' => [
                'score' => $dto->extractionQualityScore,
                'grade' => $dto->extractionQualityGrade,
                'reasons' => $dto->extractionQualityReasons,
            ],
            'raw_compiler_output' => $compiled,
        ];
    }

    public function filename(array $golden): string
    {
        $identity = $golden['identity'] ?? [];
        $parts = array_filter([
            $golden['device_type'] ?? 'record',
            $identity['manufacturer'] ?? null,
            $identity['model_name'] ?? null,
            $identity['model_series'] ?? null,
            $identity['display_name'] ?? null,
            $identity['power_class_w'] ?? null,
            $identity['power_class_kw'] ?? null,
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        $slug = Str::slug(implode('-', array_map(fn (mixed $value): string => (string) $value, $parts)));

        return ($slug !== '' ? $slug : 'engineering-record').'.json';
    }

    /**
     * @return array<string,mixed>
     */
    private function moduleRecord(ModuleDto $dto, ?ModuleElectricalModelDto $model, string $pdfPath, int $index): array
    {
        $compiled = $dto->toArray();
        $modelArray = $model?->toArray();

        $engineering = [
            'electrical_stc' => [
                'models' => $modelArray ? [$modelArray] : [],
                'metadata' => $compiled['electrical_stc']['metadata'] ?? [],
            ],
            'mechanical' => $compiled['mechanical'] ?? null,
            'operating_conditions' => $compiled['operating_conditions'] ?? null,
            'temperature_characteristics' => $compiled['temperature_characteristics'] ?? null,
            'warranty' => $compiled['warranty'] ?? null,
            'certifications' => $compiled['certifications'] ?? null,
            'packaging' => $compiled['packaging'] ?? null,
            'country_manufacturing_metadata' => $compiled['source_metadata']['country_manufacturing_metadata'] ?? null,
        ];

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'compiler_version' => self::MODULE_COMPILER_VERSION,
            'record_type' => 'module_engineering_record',
            'device_type' => 'module',
            'identity' => [
                'manufacturer' => $dto->manufacturer,
                'series' => $dto->series,
                'family' => $dto->family,
                'model_series' => $modelArray['model_series'] ?? null,
                'model_name' => $modelArray['model_variants'][0] ?? null,
                'model_variants' => $modelArray['model_variants'] ?? [],
                'display_name' => $modelArray['display_name'] ?? null,
                'power_class_w' => $modelArray['power_class_w'] ?? null,
                'technology' => $dto->technology,
            ],
            'source_datasheet' => $this->sourceDatasheet($pdfPath, $compiled['source_metadata'] ?? []),
            'manufacturer' => $dto->manufacturer,
            'model' => [
                'series' => $dto->series,
                'family' => $dto->family,
                'model_series' => $modelArray['model_series'] ?? null,
                'model_name' => $modelArray['model_variants'][0] ?? null,
                'model_variants' => $modelArray['model_variants'] ?? [],
                'display_name' => $modelArray['display_name'] ?? null,
                'power_class_w' => $modelArray['power_class_w'] ?? null,
            ],
            'engineering' => $engineering,
            'source_provenance' => $this->sourceProvenance($engineering),
            'validation_issues' => $this->validationIssues($compiled['validation'] ?? null),
            'validation' => $compiled['validation'] ?? null,
            'extraction_warnings' => $compiled['extraction_warnings'] ?? [],
            'sections' => $compiled['sections'] ?? [],
            'quality' => [
                'record_index' => $index,
                'validation_status' => $this->validationStatus($compiled['validation'] ?? null),
            ],
            'raw_compiler_output' => [
                ...$compiled,
                'electrical_stc' => $engineering['electrical_stc'],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function sourceDatasheet(string $pdfPath, array $sourceMetadata): array
    {
        return [
            'filename' => basename($pdfPath),
            'relative_path' => $this->relativePath($pdfPath),
            'sha256' => is_file($pdfPath) ? hash_file('sha256', $pdfPath) : null,
            'size_bytes' => is_file($pdfPath) ? filesize($pdfPath) : null,
            'source_metadata' => $sourceMetadata,
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function sourceProvenance(array $payload): array
    {
        $items = [];
        $this->collectSourceValues($payload, '', $items);

        return $items;
    }

    /**
     * @param list<array<string,mixed>> $items
     */
    private function collectSourceValues(mixed $value, string $path, array &$items): void
    {
        if (! is_array($value)) {
            return;
        }

        if (array_key_exists('source_text', $value) || array_key_exists('source_page', $value) || array_key_exists('source_section', $value)) {
            $items[] = [
                'field' => $path,
                'page' => $value['source_page'] ?? null,
                'section' => $value['source_section'] ?? null,
                'source_text' => $value['source_text'] ?? null,
                'confidence' => $value['confidence'] ?? null,
            ];
        }

        foreach ($value as $key => $child) {
            $childPath = $path === '' ? (string) $key : $path.'.'.$key;
            $this->collectSourceValues($child, $childPath, $items);
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function validationIssues(?array $validation): array
    {
        $issues = $validation['issues'] ?? [];

        return is_array($issues) ? array_values($issues) : [];
    }

    private function validationStatus(?array $validation): ?string
    {
        $issues = $this->validationIssues($validation);

        if ($issues === []) {
            return $validation === null ? null : 'clean';
        }

        foreach ($issues as $issue) {
            if (($issue['severity'] ?? null) === 'error') {
                return 'errors';
            }
        }

        return 'warnings';
    }

    private function relativePath(string $path): string
    {
        return ltrim(str_replace(base_path().DIRECTORY_SEPARATOR, '', $path), DIRECTORY_SEPARATOR);
    }
}
