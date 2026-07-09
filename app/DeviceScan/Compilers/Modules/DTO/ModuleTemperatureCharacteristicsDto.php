<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\DTO;

final readonly class ModuleTemperatureCharacteristicsDto
{
    public function __construct(
        public ?ModuleSourceValueDto $nominalOperatingCellTemperature = null,
        public ?ModuleSourceValueDto $temperatureCoefficientPmax = null,
        public ?ModuleSourceValueDto $temperatureCoefficientVoc = null,
        public ?ModuleSourceValueDto $temperatureCoefficientIsc = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'nominal_operating_cell_temperature' => $this->nominalOperatingCellTemperature?->toArray(),
            'temperature_coefficient_pmax' => $this->temperatureCoefficientPmax?->toArray(),
            'temperature_coefficient_voc' => $this->temperatureCoefficientVoc?->toArray(),
            'temperature_coefficient_isc' => $this->temperatureCoefficientIsc?->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}
