<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables;

final readonly class DetectedTableRow
{
    /**
     * @param DetectedTableCell[] $cells
     */
    public function __construct(
        public string $label,
        public array $cells = [],
    ) {}

    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'cells' => array_map(
                fn (DetectedTableCell $cell) => $cell->toArray(),
                $this->cells,
            ),
        ];
    }
}