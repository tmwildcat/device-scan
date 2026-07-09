<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\DTO;

final readonly class ModuleElectricalModelDto
{
    /**
     * @param string[] $modelVariants
     */
    public function __construct(
        public ?string $modelSeries,
        public array $modelVariants = [],
        public ?float $powerClassW = null,
        public ?string $displayName = null,
        public ?ModuleSourceValueDto $ratedMaxPowerW = null,
        public ?ModuleSourceValueDto $openCircuitVoltageV = null,
        public ?ModuleSourceValueDto $maximumPowerVoltageV = null,
        public ?ModuleSourceValueDto $shortCircuitCurrentA = null,
        public ?ModuleSourceValueDto $maximumPowerCurrentA = null,
        public ?ModuleSourceValueDto $moduleEfficiencyPercent = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'model_series' => $this->modelSeries,
            'model_variants' => $this->modelVariants,
            'power_class_w' => $this->powerClassW,
            'display_name' => $this->displayName ?? $this->defaultDisplayName(),
            'rated_max_power_w' => $this->ratedMaxPowerW?->toArray(),
            'open_circuit_voltage_v' => $this->openCircuitVoltageV?->toArray(),
            'maximum_power_voltage_v' => $this->maximumPowerVoltageV?->toArray(),
            'short_circuit_current_a' => $this->shortCircuitCurrentA?->toArray(),
            'maximum_power_current_a' => $this->maximumPowerCurrentA?->toArray(),
            'module_efficiency_percent' => $this->moduleEfficiencyPercent?->toArray(),
            'metadata' => $this->metadata,
        ];
    }

    private function defaultDisplayName(): ?string
    {
        if ($this->modelSeries !== null && $this->powerClassW !== null) {
            return $this->modelSeries.' '.((int) round($this->powerClassW)).'W';
        }

        return $this->modelSeries ?? ($this->powerClassW !== null ? ((int) round($this->powerClassW)).'W' : null);
    }
}
