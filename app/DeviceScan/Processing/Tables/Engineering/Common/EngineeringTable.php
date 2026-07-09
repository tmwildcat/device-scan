<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Engineering\Common;

final readonly class EngineeringTable
{
    /**
     * @param string[] $models
     * @param EngineeringTableRow[] $rows
     */
    public function __construct(
        public string $type,
        public array $models = [],
        public array $rows = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'models' => $this->models,
            'rows' => array_map(
                fn (EngineeringTableRow $row) => $row->toArray(),
                $this->rows,
            ),
            'metadata' => $this->metadata,
        ];
    }
}