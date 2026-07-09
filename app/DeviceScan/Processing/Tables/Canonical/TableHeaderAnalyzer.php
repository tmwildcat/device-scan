<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Canonical;

use App\DeviceScan\Processing\Tables\DTO\TableCell;
use App\DeviceScan\Processing\Tables\DTO\TableGrid;

final class TableHeaderAnalyzer
{
    public function analyze(TableGrid $grid): TableHeaderAnalysis
    {
        $modelHeaderRow = $this->detectModelHeaderRow($grid);
        $conditionHeaderRow = $this->detectConditionHeaderRow($grid);
        $parameterColumn = $this->detectParameterColumn($grid);
        $unitColumn = $this->detectUnitColumn($grid);

        $modelsByColumn = $modelHeaderRow !== null
            ? $this->modelsByColumn($grid, $modelHeaderRow)
            : [];
        
        $valueColumns = array_keys($modelsByColumn);

        if ($valueColumns === []) {
            $valueColumns = $this->fallbackValueColumns($grid, $parameterColumn, $unitColumn);
        }

        $dataRows = $this->detectDataRows(
            grid: $grid,
            parameterColumn: $parameterColumn,
            modelHeaderRow: $modelHeaderRow,
            conditionHeaderRow: $conditionHeaderRow,
        );

        return new TableHeaderAnalysis(
            parameterColumn: $parameterColumn,
            unitColumn: $unitColumn,
            modelHeaderRow: $modelHeaderRow,
            conditionHeaderRow: $conditionHeaderRow,
            valueColumns: $valueColumns,
            dataRows: $dataRows,
            modelsByColumn: $modelsByColumn,
            metadata: [
                'source' => self::class,
            ],
        );
    }

    private function detectModelHeaderRow(TableGrid $grid): ?int
    {
        $scores = [];

        foreach ($this->rows($grid) as $rowIndex => $cells) {
            $score = 0;

            foreach ($cells as $cell) {
                $models = $this->extractModels($cell->text);

                if ($models !== []) {
                    $score += count($models) * 10;
                }

                if (preg_match('/module\s+type/i', $cell->text)) {
                    $score += 30;
                }
            }

            if ($score > 0) {
                $scores[$rowIndex] = $score;
            }
        }

        if ($scores === []) {
            return null;
        }

        arsort($scores);

        return (int) array_key_first($scores);
    }

    private function detectConditionHeaderRow(TableGrid $grid): ?int
    {
        $scores = [];

        foreach ($this->rows($grid) as $rowIndex => $cells) {
            $score = 0;

            foreach ($cells as $cell) {
                $text = strtoupper($cell->text);

                if (str_contains($text, 'STC')) {
                    $score += 10;
                }

                if (str_contains($text, 'NOCT')) {
                    $score += 10;
                }
            }

            if ($score > 0) {
                $scores[$rowIndex] = $score;
            }
        }

        if ($scores === []) {
            return null;
        }

        arsort($scores);

        return (int) array_key_first($scores);
    }

    private function detectParameterColumn(TableGrid $grid): ?int
    {
        $scores = [];

        foreach ($grid->cells as $cell) {
            $canonical = $cell->metadata['canonical_parameter'] ?? null;

            if (! is_string($canonical) || $canonical === '') {
                continue;
            }

            $scores[$cell->column] = ($scores[$cell->column] ?? 0) + 1;
        }

        if ($scores === []) {
            return null;
        }

        arsort($scores);

        return (int) array_key_first($scores);
    }

    private function detectUnitColumn(TableGrid $grid): ?int
    {
        $scores = [];

        foreach ($grid->cells as $cell) {
            if ($this->looksLikeUnit($cell->text)) {
                $scores[$cell->column] = ($scores[$cell->column] ?? 0) + 1;
            }
        }

        if ($scores === []) {
            return null;
        }

        arsort($scores);

        return (int) array_key_first($scores);
    }

    /**
     * @return array<int,string>
     */
    private function modelsByColumn(TableGrid $grid, int $modelHeaderRow): array
    {
        $models = [];

        foreach ($this->cellsInRow($grid, $modelHeaderRow) as $cell) {
            $extracted = $this->extractModels($cell->text);

            if ($extracted === []) {
                continue;
            }

            $models[$cell->column] = $extracted[0];
        }

        ksort($models);

        return $models;
    }

    /**
     * @return int[]
     */
    private function fallbackValueColumns(TableGrid $grid, ?int $parameterColumn, ?int $unitColumn): array
    {
        $columns = [];

        foreach ($grid->cells as $cell) {
            if ($cell->column === $parameterColumn || $cell->column === $unitColumn) {
                continue;
            }

            if (preg_match('/\d/', $cell->text)) {
                $columns[$cell->column] = true;
            }
        }

        return array_keys($columns);
    }

    /**
 * @return int[]
 */
private function detectDataRows(
    TableGrid $grid,
    ?int $parameterColumn,
    ?int $modelHeaderRow,
    ?int $conditionHeaderRow,
): array {
    $headerCutoff = max(array_filter([
        $modelHeaderRow,
        $conditionHeaderRow,
    ], fn ($v) => $v !== null) ?: [-1]);

    $rows = [];

    foreach ($this->rows($grid) as $rowIndex => $cells) {
        if ($rowIndex <= $headerCutoff) {
            continue;
        }

        $rowText = $this->cleanRowText($cells);

        $hasCanonicalParameter = false;

        foreach ($cells as $cell) {
            if ($parameterColumn !== null && $cell->column !== $parameterColumn) {
                continue;
            }

            $canonical = $cell->metadata['canonical_parameter'] ?? null;

            if (is_string($canonical) && $canonical !== '') {
                $hasCanonicalParameter = true;
                break;
            }
        }

        if ($hasCanonicalParameter || $this->looksLikeModuleElectricalDataRow($rowText)) {
            $rows[] = (int) $rowIndex;
        }
    }

    return array_values(array_unique($rows));
}

private function cleanRowText(array $cells): string
{
    usort($cells, fn (TableCell $a, TableCell $b) => $a->column <=> $b->column);

    $text = strtolower(trim(implode(' ', array_map(
        fn (TableCell $cell) => $cell->text,
        $cells,
    ))));

    $text = str_replace(['(', ')', '[', ']'], ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;

    return trim($text);
}

private function looksLikeModuleElectricalDataRow(string $rowText): bool
{
    foreach ([
        'maximum power',
        'rated maximum power',
        'pmax',
        'open circuit voltage',
        'open-circuit voltage',
        'voc',
        'maximum power voltage',
        'voltage at maximum power',
        'vmp',
        'maximum power current',
        'current at maximum power',
        'imp',
        'short circuit current',
        'short-circuit current',
        'isc',
        'module efficiency',
        'efficiency',
    ] as $signal) {
        if (str_contains($rowText, $signal)) {
            return true;
        }
    }

    return false;
}

    /**
     * @return array<int, TableCell[]>
     */
    private function rows(TableGrid $grid): array
    {
        $rows = [];

        foreach ($grid->cells as $cell) {
            $rows[$cell->row][] = $cell;
        }

        ksort($rows);

        return $rows;
    }

    /**
     * @return TableCell[]
     */
    private function cellsInRow(TableGrid $grid, int $row): array
    {
        return array_values(array_filter(
            $grid->cells,
            fn (TableCell $cell) => $cell->row === $row,
        ));
    }

    /**
 * @return string[]
 */
private function extractModels(string $text): array
{
    $lower = strtolower($text);

    // Do not treat standards/certifications as module models.
    if (
        str_contains($lower, 'iec')
        || str_contains($lower, 'ul ')
        || str_contains($lower, 'en ')
        || str_contains($lower, 'iso')
        || str_contains($lower, 'certificat')
    ) {
        return [];
    }

    preg_match_all(
        '/(?<!\d)(?:JKM|JAM|LR|TSM|CS|Hi-MO|Tiger)?[A-Z0-9\-\/]*?(5\d{2}|6\d{2}|7\d{2})(?:\s?W?p?)?(?!\d)/i',
        $text,
        $matches,
    );

    $models = [];

    foreach ($matches[1] ?? [] as $match) {
        $value = (int) $match;

        if ($value >= 300 && $value <= 800) {
            $models[(string) $value] = true;
        }
    }

    return array_keys($models);
}

    private function looksLikeUnit(string $text): bool
    {
        return preg_match('/^\s*(W|Wp|V|A|%|°C|℃|kW|kWh)\s*$/iu', $text) === 1;
    }
}