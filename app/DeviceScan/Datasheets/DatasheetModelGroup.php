<?php

declare(strict_types=1);

namespace App\DeviceScan\Datasheets;

final readonly class DatasheetModelGroup
{
    /**
     * @param string[] $models
     * @param DatasheetTable[] $tables
     */
    public function __construct(
        public string $name,
        public array $models = [],
        public array $tables = [],
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'models' => $this->models,
            'tables' => array_map(
                fn (DatasheetTable $table) => $table->toArray(),
                $this->tables
            ),
        ];
    }
}