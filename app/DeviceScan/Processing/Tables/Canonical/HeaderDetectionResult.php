<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Canonical;

final readonly class HeaderDetectionResult
{
    /**
     * @param int[] $valueColumns
     * @param int[] $dataRows
     */
    public function __construct(
        public ?int $parameterColumn = null,
        public ?int $modelHeaderRow = null,
        public array $valueColumns = [],
        public array $dataRows = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'parameter_column' => $this->parameterColumn,
            'model_header_row' => $this->modelHeaderRow,
            'value_columns' => $this->valueColumns,
            'data_rows' => $this->dataRows,
            'metadata' => $this->metadata,
        ];
    }
}