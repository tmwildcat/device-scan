<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Canonical;

use App\DeviceScan\Processing\Tables\DTO\TableCell;
use App\DeviceScan\Processing\Tables\DTO\TableGrid;

final class HeaderDetector
{
    public function detect(TableGrid $grid): HeaderDetectionResult
    {
        $parameterColumn = $this->detectParameterColumn($grid);
        $modelHeaderRow = $this->detectModelHeaderRow($grid);

        $valueColumns = $modelHeaderRow === null
            ? []
            : $this->detectValueColumns($grid, $modelHeaderRow);

        $dataRows = $parameterColumn === null
            ? []
            : $this->detectDataRows($grid, $parameterColumn);

        return new HeaderDetectionResult(
            parameterColumn: $parameterColumn,
            modelHeaderRow: $modelHeaderRow,
            valueColumns: $valueColumns,
            dataRows: $dataRows,
            metadata: [
                'source' => self::class,
            ],
        );
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

    private function detectModelHeaderRow(TableGrid $grid): ?int
    {
        $scores = [];

        foreach ($grid->cells as $cell) {
            if (! $this->looksLikeModuleRating($cell->text)) {
                continue;
            }

            $scores[$cell->row] = ($scores[$cell->row] ?? 0) + 1;
        }

        if ($scores === []) {
            return null;
        }

        arsort($scores);

        return (int) array_key_first($scores);
    }

    /**
     * @return int[]
     */
    private function detectValueColumns(TableGrid $grid, int $modelHeaderRow): array
    {
        $columns = [];

        foreach ($grid->cells as $cell) {
            if ($cell->row !== $modelHeaderRow) {
                continue;
            }

            if (! $this->looksLikeModuleRating($cell->text)) {
                continue;
            }

            $columns[] = $cell->column;
        }

        sort($columns);

        return array_values(array_unique($columns));
    }

    /**
     * @return int[]
     */
    private function detectDataRows(TableGrid $grid, int $parameterColumn): array
    {
        $rows = [];

        foreach ($grid->cells as $cell) {
            if ($cell->column !== $parameterColumn) {
                continue;
            }

            $canonical = $cell->metadata['canonical_parameter'] ?? null;

            if (is_string($canonical) && $canonical !== '') {
                $rows[] = $cell->row;
            }
        }

        sort($rows);

        return array_values(array_unique($rows));
    }

    private function looksLikeModuleRating(string $text): bool
    {
        $text = trim($text);

        if (! preg_match('/\b(\d{3})\b/u', $text, $match)) {
            return false;
        }

        $rating = (int) $match[1];

        return $rating >= 300 && $rating <= 900;
    }
}