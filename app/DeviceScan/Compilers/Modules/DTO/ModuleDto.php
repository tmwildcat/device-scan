<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\DTO;

use App\DeviceScan\Compilers\Modules\Validation\ModuleValidationResult;

final readonly class ModuleDto
{
    /**
     * @param string[] $models
     * @param ModuleDetectedSectionDto[] $sections
     * @param string[] $warnings
     */
    public function __construct(
        public ?string $manufacturer = null,
        public ?string $series = null,
        public ?string $family = null,
        public ?string $technology = null,
        public array $models = [],
        public ?ModuleElectricalStcDto $electricalStc = null,
        public array $electricalVariants = [],
        public ?ModuleMechanicalDto $mechanical = null,
        public ?ModuleOperatingConditionsDto $operatingConditions = null,
        public ?ModuleTemperatureCharacteristicsDto $temperatureCharacteristics = null,
        public ?ModuleWarrantyDto $warranty = null,
        public ?ModulePackagingDto $packaging = null,
        public ?ModuleCertificationsDto $certifications = null,
        public ?ModuleValidationResult $validation = null,
        public array $sections = [],
        public array $sourceMetadata = [],
        public array $extractionWarnings = [],
        public array $warnings = [],
    ) {}

    public function toArray(): array
    {
        return [
            'manufacturer' => $this->manufacturer,
            'series' => $this->series,
            'family' => $this->family,
            'technology' => $this->technology,
            'models' => $this->models,
            'electrical_stc' => $this->electricalStc?->toArray(),
            'electrical_variants' => $this->electricalVariants,
            'mechanical' => $this->mechanical?->toArray(),
            'operating_conditions' => $this->operatingConditions?->toArray(),
            'temperature_characteristics' => $this->temperatureCharacteristics?->toArray(),
            'warranty' => $this->warranty?->toArray(),
            'packaging' => $this->packaging?->toArray(),
            'certifications' => $this->certifications?->toArray(),
            'validation' => $this->validation?->toArray(),
            'sections' => array_map(
                fn (ModuleDetectedSectionDto $section) => $section->toArray(),
                $this->sections,
            ),
            'source_metadata' => $this->sourceMetadata,
            'extraction_warnings' => $this->extractionWarnings,
            'warnings' => $this->warnings,
        ];
    }
}
