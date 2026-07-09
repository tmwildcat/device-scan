<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Engineering\Module\Electrical;

final readonly class ModuleElectricalRowAnalysis
{
    public function __construct(
        public array $stcRows = [],
        public array $noctRows = [],
        public array $temperatureCoefficientRows = [],
        public array $ignoredRows = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'stc_rows' => $this->stcRows,
            'noct_rows' => $this->noctRows,
            'temperature_coefficient_rows' => $this->temperatureCoefficientRows,
            'ignored_rows' => $this->ignoredRows,
            'metadata' => $this->metadata,
        ];
    }
}