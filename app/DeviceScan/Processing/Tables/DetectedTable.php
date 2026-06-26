<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables;

final readonly class DetectedTable
{
    /**
     * @param string[] $models
     * @param DetectedTableRow[] $rows
     */
    public function __construct(
        public string $title,
        public int $page,
        public array $models = [],
        public array $rows = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'page' => $this->page,
            'models' => $this->models,
            'metadata' => $this->metadata,
            'rows' => array_map(
                fn (DetectedTableRow $row) => $row->toArray(),
                $this->rows,
            ),
        ];
    }
}