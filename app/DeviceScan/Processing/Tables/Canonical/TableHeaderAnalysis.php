<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Canonical;

final readonly class TableHeaderAnalysis
{
    /**
     * @param int[] $valueColumns
     * @param int[] $dataRows
     * @param array<int,string> $modelsByColumn
     */
    public function __construct(
        public ?int $parameterColumn,
        public ?int $unitColumn,
        public ?int $modelHeaderRow,
        public ?int $conditionHeaderRow,
        public array $valueColumns,
        public array $dataRows,
        public array $modelsByColumn = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'parameter_column' => $this->parameterColumn,
            'unit_column' => $this->unitColumn,
            'model_header_row' => $this->modelHeaderRow,
            'condition_header_row' => $this->conditionHeaderRow,
            'value_columns' => $this->valueColumns,
            'data_rows' => $this->dataRows,
            'models_by_column' => $this->modelsByColumn,
            'metadata' => $this->metadata,
        ];
    }
}