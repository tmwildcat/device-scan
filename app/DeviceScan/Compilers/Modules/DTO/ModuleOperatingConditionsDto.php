<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\DTO;

final readonly class ModuleOperatingConditionsDto
{
    public function __construct(
        public ?ModuleSourceValueDto $maximumSystemVoltage = null,
        public ?ModuleSourceValueDto $operatingTemperature = null,
        public ?ModuleSourceValueDto $maximumSeriesFuseRating = null,
        public ?ModuleSourceValueDto $staticLoadFront = null,
        public ?ModuleSourceValueDto $staticLoadBack = null,
        public ?ModuleSourceValueDto $safetyClass = null,
        public ?ModuleSourceValueDto $fireRating = null,
        public ?ModuleSourceValueDto $bifaciality = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'maximum_system_voltage' => $this->maximumSystemVoltage?->toArray(),
            'operating_temperature' => $this->operatingTemperature?->toArray(),
            'maximum_series_fuse_rating' => $this->maximumSeriesFuseRating?->toArray(),
            'static_load_front' => $this->staticLoadFront?->toArray(),
            'static_load_back' => $this->staticLoadBack?->toArray(),
            'safety_class' => $this->safetyClass?->toArray(),
            'fire_rating' => $this->fireRating?->toArray(),
            'bifaciality' => $this->bifaciality?->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}
