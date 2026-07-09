<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\DTO;

final readonly class InverterProtectionDto
{
    public function __construct(
        public ?InverterSourceValueDto $hasDcSwitch = null,
        public ?InverterSourceValueDto $hasDcDisconnector = null,
        public ?InverterSourceValueDto $hasDcReversePolarityProtection = null,
        public ?InverterSourceValueDto $hasDcSpd = null,
        public ?InverterSourceValueDto $dcSpdType = null,
        public ?InverterSourceValueDto $hasAcSpd = null,
        public ?InverterSourceValueDto $acSpdType = null,
        public ?InverterSourceValueDto $hasAcShortCircuitProtection = null,
        public ?InverterSourceValueDto $hasAcOvercurrentProtection = null,
        public ?InverterSourceValueDto $hasAntiIslandingProtection = null,
        public ?InverterSourceValueDto $hasGroundFaultMonitoring = null,
        public ?InverterSourceValueDto $hasInsulationMonitoring = null,
        public ?InverterSourceValueDto $hasResidualCurrentMonitoring = null,
        public ?InverterSourceValueDto $hasRcmu = null,
        public ?InverterSourceValueDto $hasAfci = null,
        public ?InverterSourceValueDto $hasPidRecovery = null,
        public ?InverterSourceValueDto $hasStringCurrentMonitoring = null,
        public ?InverterSourceValueDto $hasGridMonitoring = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'has_dc_switch' => $this->hasDcSwitch?->toArray(),
            'has_dc_disconnector' => $this->hasDcDisconnector?->toArray(),
            'has_dc_reverse_polarity_protection' => $this->hasDcReversePolarityProtection?->toArray(),
            'has_dc_spd' => $this->hasDcSpd?->toArray(),
            'dc_spd_type' => $this->dcSpdType?->toArray(),
            'has_ac_spd' => $this->hasAcSpd?->toArray(),
            'ac_spd_type' => $this->acSpdType?->toArray(),
            'has_ac_short_circuit_protection' => $this->hasAcShortCircuitProtection?->toArray(),
            'has_ac_overcurrent_protection' => $this->hasAcOvercurrentProtection?->toArray(),
            'has_anti_islanding_protection' => $this->hasAntiIslandingProtection?->toArray(),
            'has_ground_fault_monitoring' => $this->hasGroundFaultMonitoring?->toArray(),
            'has_insulation_monitoring' => $this->hasInsulationMonitoring?->toArray(),
            'has_residual_current_monitoring' => $this->hasResidualCurrentMonitoring?->toArray(),
            'has_rcmu' => $this->hasRcmu?->toArray(),
            'has_afci' => $this->hasAfci?->toArray(),
            'has_pid_recovery' => $this->hasPidRecovery?->toArray(),
            'has_string_current_monitoring' => $this->hasStringCurrentMonitoring?->toArray(),
            'has_grid_monitoring' => $this->hasGridMonitoring?->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}
