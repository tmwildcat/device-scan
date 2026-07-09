<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Engineering\Common;

final readonly class EngineeringTableRow
{
    public function __construct(
        public string $parameter,
        public ?string $unit,
        public array $values = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'parameter' => $this->parameter,
            'unit' => $this->unit,
            'values' => $this->values,
            'metadata' => $this->metadata,
        ];
    }
}