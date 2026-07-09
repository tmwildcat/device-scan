<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Engineering\Module\Electrical;

final readonly class ModuleElectricalParameterAnalysis
{
    /**
     * @param array<int,array{
     *     canonical:string,
     *     confidence:float,
     *     method:string,
     *     source_text:string
     * }> $rows
     */
    public function __construct(
        public array $rows = [],
        public array $metadata = [],
    ) {}

    public function parameterForRow(int $row): ?string
    {
        $parameter = $this->rows[$row]['canonical'] ?? null;

        return is_string($parameter) && $parameter !== ''
            ? $parameter
            : null;
    }

    public function confidenceForRow(int $row): float
    {
        return (float) ($this->rows[$row]['confidence'] ?? 0.0);
    }

    public function toArray(): array
    {
        return [
            'rows' => $this->rows,
            'metadata' => $this->metadata,
        ];
    }
}