<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\DTO;
use App\DeviceScan\Processing\Tables\Canonical\HeaderDetectionResult;


final readonly class TableGrid
{
    /**
     * @param int[] $columns
     * @param int[] $rows
     * @param TableCell[] $cells
     */
    public function __construct(
        public string $type,
        public array $columns,
        public array $rows,
        public array $cells,
        public ?HeaderDetectionResult $headerDetection = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'columns' => $this->columns,
            'rows' => $this->rows,
            'cells' => array_map(
                fn (TableCell $cell) => $cell->toArray(),
                $this->cells,
            ),
            'header_detection' => $this->headerDetection?->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}