<?php

namespace App\DeviceScan\Extraction\Matrix;

use App\DeviceScan\Document\EngineeringMatrix;
use App\DeviceScan\Document\EngineeringMatrixRow;
use App\DeviceScan\Metadata\CandidateCollection;
use App\DeviceScan\Metadata\CandidateValue;
use App\DeviceScan\Document\EngineeringColumn;

class ModuleMatrixExtractor
{
    public function extract(EngineeringMatrix $matrix): CandidateCollection
    {
        $collection = new CandidateCollection();

        $columnMap = $this->buildColumnMap($matrix);

        foreach ($matrix->rows as $row) {

            if (!$row->canonicalField) {
                continue;
            }

            foreach ($row->cells as $index => $cell) {

                $column = $columnMap[$index] ?? null;

                if (!$column) {
                    continue;
                }

                $collection->add(
                    new CandidateValue(
                        field: $row->canonicalField,

                        value: $cell->numericValue,

                        confidence: 1.0,

                        unit: $cell->unit,

                        source: 'engineering_matrix',

                        metadata: [
                            'model' => $column->model,
                            'condition' => $column->condition,
                            'row_label' => $row->label,
                            'column' => $index,
                        ],
                    )
                );
            }
        }

        return $collection;
    }

    /**
        * @return array<int, EngineeringColumn>
     */
    private function buildColumnMap(
            EngineeringMatrix $matrix
        ): array {

            $map = [];

            foreach ($matrix->columns as $column) {

                $map[$column->index] = $column;
            }

            return $map;
    }

    private function findRow(
        EngineeringMatrix $matrix,
        string $field
    ): ?EngineeringMatrixRow {

        foreach ($matrix->rows as $row) {

            if ($row->canonicalField === $field) {
                return $row;
            }
        }

        return null;
    }
}