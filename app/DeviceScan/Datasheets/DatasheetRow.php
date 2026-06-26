<?php

declare(strict_types=1);

namespace App\DeviceScan\Datasheets;

final readonly class DatasheetRow
{
    /**
     * @param DatasheetCell[] $cells
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
                fn (DatasheetCell $cell) => $cell->toArray(),
                $this->cells
            ),
        ];
    }
}