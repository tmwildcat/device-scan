<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\DTO;

final readonly class InverterCentralSpecificDto
{
    public function __construct(
        public ?InverterSourceValueDto $maxDcInputs = null,
        public ?InverterSourceValueDto $dcCabinetInputs = null,
        public ?InverterSourceValueDto $dcCombinerRequired = null,
        public ?InverterSourceValueDto $mvStationInterface = null,
        public ?InverterSourceValueDto $transformerInterface = null,
        public ?InverterSourceValueDto $gridVoltageMv = null,
        public ?InverterSourceValueDto $acBreaker = null,
        public ?InverterSourceValueDto $coolingSystem = null,
        public ?InverterSourceValueDto $containerized = null,
        public ?InverterSourceValueDto $mpptCount = null,
        public ?InverterSourceValueDto $inverterBlocks = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'max_dc_inputs' => $this->maxDcInputs?->toArray(),
            'dc_cabinet_inputs' => $this->dcCabinetInputs?->toArray(),
            'dc_combiner_required' => $this->dcCombinerRequired?->toArray(),
            'mv_station_interface' => $this->mvStationInterface?->toArray(),
            'transformer_interface' => $this->transformerInterface?->toArray(),
            'grid_voltage_mv' => $this->gridVoltageMv?->toArray(),
            'ac_breaker' => $this->acBreaker?->toArray(),
            'cooling_system' => $this->coolingSystem?->toArray(),
            'containerized' => $this->containerized?->toArray(),
            'mppt_count' => $this->mpptCount?->toArray(),
            'inverter_blocks' => $this->inverterBlocks?->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}
