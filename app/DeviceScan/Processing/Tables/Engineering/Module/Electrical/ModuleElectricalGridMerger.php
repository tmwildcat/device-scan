<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Engineering\Module\Electrical;

use App\DeviceScan\Processing\Tables\DTO\TableCell;
use App\DeviceScan\Processing\Tables\DTO\TableGrid;

final class ModuleElectricalGridMerger
{
    /**
     * @param TableGrid[] $grids
     * @return TableGrid[]
     */
    public function merge(array $grids): array
    {
        $electrical = [];
        $other = [];

        foreach ($grids as $grid) {
            if ($this->looksLikeModuleElectricalFragment($grid)) {
                $electrical[] = $grid;
            } else {
                $other[] = $grid;
            }
        }

        if (count($electrical) < 2) {
            return $grids;
        }

        $merged = $this->mergeElectricalGrids($electrical);

        return [
            ...$other,
            $merged,
        ];
    }

    private function looksLikeModuleElectricalFragment(TableGrid $grid): bool
    {
        $text = strtolower($this->gridText($grid));

        if ($grid->type === 'mechanical') {
            return false;
        }

        foreach ([
            'rated maximum power',
            'rated max power',
            'maximum power',
            'open circuit voltage',
            'open-circuit voltage',
            'maximum power voltage',
            'short circuit current',
            'short-circuit current',
            'maximum power current',
            'module efficiency',
            'temperature coefficient',
            'power tolerance',
            'pmax',
            'voc',
            'vmp',
            'isc',
            'imp',
        ] as $signal) {
            if (str_contains($text, $signal)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param TableGrid[] $grids
     */
    private function mergeElectricalGrids(array $grids): TableGrid
    {
        usort(
            $grids,
            fn (TableGrid $a, TableGrid $b) => $this->top($a) <=> $this->top($b),
        );

        $modelColumns = $this->bestModelColumns($grids);
        $allCells = [];
        $rowOffset = 0;

        foreach ($grids as $grid) {
            $rowMap = $this->rowMap($grid);

            foreach ($grid->cells as $cell) {
                $allCells[] = new TableCell(
                    row: $rowMap[$cell->row] + $rowOffset,
                    column: $this->normalizeColumn($cell, $modelColumns),
                    text: $cell->text,
                    left: $cell->left,
                    top: $cell->top,
                    width: $cell->width,
                    height: $cell->height,
                    metadata: [
                        ...$cell->metadata,
                        'merged_from_grid_type' => $grid->type,
                        'original_row' => $cell->row,
                        'original_column' => $cell->column,
                    ],
                    ocrText: $cell->ocrText,
                    nativeText: $cell->nativeText,
                    textSource: $cell->textSource,
                );
            }

            $rowOffset += count($rowMap);
        }

        $columns = array_values(array_unique(array_map(
            fn (TableCell $cell) => $cell->column,
            $allCells,
        )));

        sort($columns);

        $rows = array_values(array_unique(array_map(
            fn (TableCell $cell) => $cell->row,
            $allCells,
        )));

        sort($rows);

        return new TableGrid(
            type: 'electrical',
            columns: $columns,
            rows: $rows,
            cells: $allCells,
            metadata: [
                'source' => self::class,
                'merged' => true,
                'merged_grid_count' => count($grids),
                'model_columns' => $modelColumns,
            ],
        );
    }

    /**
     * @param TableGrid[] $grids
     * @return int[]
     */
    private function bestModelColumns(array $grids): array
    {
        $best = [];

        foreach ($grids as $grid) {
            $columns = [];

            foreach ($grid->cells as $cell) {
                if ($this->extractModels($cell->text) !== []) {
                    $columns[] = $cell->column;
                }
            }

            $columns = array_values(array_unique($columns));

            if (count($columns) > count($best)) {
                $best = $columns;
            }
        }

        sort($best);

        return $best;
    }

    /**
     * @return array<int,int>
     */
    private function rowMap(TableGrid $grid): array
    {
        $rows = array_values(array_unique(array_map(
            fn (TableCell $cell) => $cell->row,
            $grid->cells,
        )));

        sort($rows);

        $map = [];

        foreach ($rows as $index => $row) {
            $map[$row] = $index;
        }

        return $map;
    }

    /**
     * Phase 1: keep existing columns.
     * Later we can align split JA columns using model headers.
     *
     * @param int[] $modelColumns
     */
    private function normalizeColumn(TableCell $cell, array $modelColumns): int
    {
        return $cell->column;
    }

    private function top(TableGrid $grid): int
    {
        if ($grid->cells === []) {
            return PHP_INT_MAX;
        }

        return min(array_map(fn (TableCell $cell) => $cell->top, $grid->cells));
    }

    private function gridText(TableGrid $grid): string
    {
        return trim(implode(' ', array_map(
            fn (TableCell $cell) => $cell->text,
            $grid->cells,
        )));
    }

    /**
     * @return string[]
     */
    private function extractModels(string $text): array
    {
        $lower = strtolower($text);

        if (
            str_contains($lower, 'iec')
            || str_contains($lower, 'ul ')
            || str_contains($lower, 'iso')
        ) {
            return [];
        }

        preg_match_all(
            '/(?<!\d)(5\d{2}|6\d{2}|7\d{2})(?!\d)/',
            $text,
            $matches,
        );

        return array_values(array_unique($matches[1] ?? []));
    }
}