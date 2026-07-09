<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Native\Analysis;

use App\DeviceScan\Processing\Native\DTO\NativeColumn;
use App\DeviceScan\Processing\Native\DTO\NativeLine;
use App\DeviceScan\Processing\Native\DTO\NativeWord;
use App\DeviceScan\Processing\Tables\DTO\TableCell;
use App\DeviceScan\Processing\Tables\DTO\TableGrid;
use App\DeviceScan\Processing\Tables\DTO\TableRegion;

final class NativeGridBuilder
{
    /**
     * @param NativeLine[] $lines
     * @param NativeColumn[] $columns
     */
    public function build(TableRegion $region, array $lines, array $columns): ?TableGrid
    {
        $lines = array_values(array_filter(
            $lines,
            fn (NativeLine $line) =>
                $line->top >= $region->top
                && $line->bottom() <= $region->bottom()
                && $line->right() >= $region->left
                && $line->left <= $region->right(),
        ));

        if (count($lines) < 2 || count($columns) < 2) {
            return null;
        }

        $columnXs = array_map(
            fn (NativeColumn $column) => (int) round($column->x),
            $columns,
        );

        $rowYs = array_map(
            fn (NativeLine $line) => (int) round($line->top),
            $lines,
        );

        $cells = [];

        foreach ($lines as $rowIndex => $line) {
            $bucketed = [];

            foreach ($line->words as $word) {
                if (! $this->wordIntersectsRegion($word, $region)) {
                    continue;
                }

                $columnIndex = $this->nearestColumn($word->left, $columns);
                $bucketed[$columnIndex][] = $word;
            }

            foreach ($bucketed as $columnIndex => $words) {
                usort(
                    $words,
                    fn (NativeWord $a, NativeWord $b) => $a->left <=> $b->left,
                );

                $left = (int) floor(min(array_map(fn (NativeWord $word) => $word->left, $words)));
                $top = (int) floor(min(array_map(fn (NativeWord $word) => $word->top, $words)));
                $right = (int) ceil(max(array_map(fn (NativeWord $word) => $word->right(), $words)));
                $bottom = (int) ceil(max(array_map(fn (NativeWord $word) => $word->bottom(), $words)));

                $text = trim(implode(' ', array_map(fn (NativeWord $word) => $word->text, $words)));

                $cells[] = new TableCell(
                    row: $rowIndex,
                    column: (int) $columnIndex,
                    text: $text,
                    left: $left,
                    top: $top,
                    width: max(1, $right - $left),
                    height: max(1, $bottom - $top),
                    metadata: [
                        'source' => self::class,
                        'native_word_count' => count($words),
                    ],
                    ocrText: null,
                    nativeText: $text,
                    textSource: 'native_pdf',
                );
            }
        }

        if (count($cells) < 4) {
            return null;
        }

        return new TableGrid(
            type: $region->type,
            columns: $columnXs,
            rows: $rowYs,
            cells: $cells,
            metadata: [
                'source' => self::class,
                'region' => [
                    'type' => $region->type,
                    'left' => $region->left,
                    'top' => $region->top,
                    'width' => $region->width,
                    'height' => $region->height,
                ],
            ],
        );
    }

    /**
     * @param NativeColumn[] $columns
     */
    private function nearestColumn(float $x, array $columns): int
    {
        $bestIndex = 0;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($columns as $column) {
            $distance = abs($x - $column->x);

            if ($distance < $bestDistance) {
                $bestIndex = $column->index;
                $bestDistance = $distance;
            }
        }

        return $bestIndex;
    }

    private function wordIntersectsRegion(NativeWord $word, TableRegion $region): bool
    {
        return $word->right() >= $region->left
            && $word->left <= $region->right()
            && $word->bottom() >= $region->top
            && $word->top <= $region->bottom();
    }
}