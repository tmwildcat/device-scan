<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Engineering\Module\Electrical;

final readonly class CanonicalModuleElectricalVariant
{
    public function __construct(
        public string $model,
        public ?float $ratedMaxPowerW = null,
        public ?float $openCircuitVoltageV = null,
        public ?float $maximumPowerVoltageV = null,
        public ?float $shortCircuitCurrentA = null,
        public ?float $maximumPowerCurrentA = null,
        public ?float $moduleEfficiencyPercent = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'rated_max_power_w' => $this->ratedMaxPowerW,
            'open_circuit_voltage_v' => $this->openCircuitVoltageV,
            'maximum_power_voltage_v' => $this->maximumPowerVoltageV,
            'short_circuit_current_a' => $this->shortCircuitCurrentA,
            'maximum_power_current_a' => $this->maximumPowerCurrentA,
            'module_efficiency_percent' => $this->moduleEfficiencyPercent,
            'metadata' => $this->metadata,
        ];
    }
}