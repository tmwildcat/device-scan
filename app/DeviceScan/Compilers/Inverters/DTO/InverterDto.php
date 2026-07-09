<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\DTO;

use App\DeviceScan\Compilers\Inverters\Validation\InverterValidationResult;

final readonly class InverterDto
{
    /**
     * @param string[] $models
     * @param InverterRatedPowerConditionDto[] $ratedPowerConditions
     * @param string[] $extractionWarnings
     * @param InverterDetectedSectionDto[] $sections
     */
    public function __construct(
        public ?string $manufacturer = null,
        public ?string $series = null,
        public ?string $modelSeries = null,
        public ?string $modelName = null,
        public ?float $powerClassKw = null,
        public ?string $displayName = null,
        public array $models = [],
        public ?string $deviceType = 'unknown',
        public ?string $unsupportedReason = null,
        public ?InverterDcInputDto $dcInput = null,
        public ?InverterAcOutputDto $acOutput = null,
        public array $ratedPowerConditions = [],
        public ?InverterProtectionDto $protection = null,
        public ?InverterCentralSpecificDto $centralSpecific = null,
        public ?InverterValidationResult $validation = null,
        public ?int $extractionQualityScore = null,
        public ?string $extractionQualityGrade = null,
        public array $extractionQualityReasons = [],
        public array $sections = [],
        public array $extractionWarnings = [],
        public array $sourceMetadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'manufacturer' => $this->manufacturer,
            'series' => $this->series,
            'model_series' => $this->modelSeries,
            'model_name' => $this->modelName,
            'power_class_kw' => $this->powerClassKw,
            'display_name' => $this->displayName,
            'models' => $this->models,
            'device_type' => $this->deviceType,
            'unsupported_reason' => $this->unsupportedReason,
            'dc_input' => $this->dcInput?->toArray(),
            'ac_output' => $this->acOutput?->toArray(),
            'rated_power_conditions' => array_map(
                fn (InverterRatedPowerConditionDto $condition) => $condition->toArray(),
                $this->ratedPowerConditions,
            ),
            'protection' => $this->protection?->toArray(),
            'central_specific' => $this->centralSpecific?->toArray(),
            'validation' => $this->validation?->toArray(),
            'extraction_quality_score' => $this->extractionQualityScore,
            'extraction_quality_grade' => $this->extractionQualityGrade,
            'extraction_quality_reasons' => $this->extractionQualityReasons,
            'sections' => array_map(
                fn (InverterDetectedSectionDto $section) => $section->toArray(),
                $this->sections,
            ),
            'extraction_warnings' => $this->extractionWarnings,
            'source_metadata' => $this->sourceMetadata,
        ];
    }
}
