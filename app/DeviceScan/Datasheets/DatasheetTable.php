<?php

declare(strict_types=1);

namespace App\DeviceScan\Datasheets;

final readonly class DatasheetTable
{
    /**
     * @param DatasheetRow[] $rows
     */
    public function __construct(
        public ?string $title = null,
        public array $rows = [],
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'rows' => array_map(
                fn (DatasheetRow $row) => $row->toArray(),
                $this->rows
            ),
        ];
    }
}