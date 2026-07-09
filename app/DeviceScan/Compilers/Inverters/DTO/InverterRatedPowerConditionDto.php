<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\DTO;

final readonly class InverterRatedPowerConditionDto
{
    public function __construct(
        public ?float $powerKw = null,
        public ?float $ambientTemperatureC = null,
        public ?string $condition = null,
        public ?InverterSourceValueDto $source = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'power_kw' => $this->powerKw,
            'ambient_temperature_c' => $this->ambientTemperatureC,
            'condition' => $this->condition,
            'source' => $this->source?->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}
