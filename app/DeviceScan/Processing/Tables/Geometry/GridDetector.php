<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Geometry;

use App\DeviceScan\Processing\Tables\DTO\TableCell;
use App\DeviceScan\Processing\Tables\DTO\TableGrid;
use App\DeviceScan\Processing\Tables\DTO\TableRegion;
use App\DeviceScan\Processing\Tables\Geometry\TextRun;

final class GridDetector
{
    public function __construct(
        private readonly TextRunBuilder $textRunBuilder,
    ) {}

    public function detect(TableRegion $region): ?TableGrid
    {
        $runs = $this->textRunBuilder->build($region->block->lines);

        if (count($runs) < 3) {
            return null;
        }

        $columns = $this->clusterPositions(
            array_map(fn (TextRun $run) => $run->left, $runs),
            tolerance: 42,
        );

        $rows = $this->clusterPositions(
            array_map(fn (TextRun $run) => $run->top, $runs),
            tolerance: 18,
        );

        if (count($columns) < 2 || count($rows) < 2) {
            return null;
        }

        $cells = $this->buildCells($runs, $columns, $rows);

        return new TableGrid(
            type: $region->type,
            columns: $columns,
            rows: $rows,
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
     * @param  int[]  $positions
     * @return int[]
     */
    private function clusterPositions(array $positions, int $tolerance): array
    {
        sort($positions);

        $clusters = [];

        foreach ($positions as $position) {
            if ($clusters === []) {
                $clusters[] = [$position];

                continue;
            }

            $lastIndex = array_key_last($clusters);
            $currentAverage = (int) round(array_sum($clusters[$lastIndex]) / count($clusters[$lastIndex]));

            if (abs($position - $currentAverage) <= $tolerance) {
                $clusters[$lastIndex][] = $position;

                continue;
            }

            $clusters[] = [$position];
        }

        return array_map(
            fn (array $cluster) => (int) round(array_sum($cluster) / count($cluster)),
            $clusters,
        );
    }

    /**
     * @param  TextRun[]  $runs
     * @param  int[]  $columns
     * @param  int[]  $rows
     * @return TableCell[]
     */
    private function buildCells(array $runs, array $columns, array $rows): array
    {
        $bucketed = [];

        foreach ($runs as $run) {
            $row = $this->nearestIndex($run->top, $rows);
            $column = $this->nearestIndex($run->left, $columns);

            $bucketed[$row][$column][] = $run;
        }

        $cells = [];

        foreach ($bucketed as $row => $columnsRuns) {
            foreach ($columnsRuns as $column => $cellRuns) {

                usort(
                    $cellRuns,
                    fn (TextRun $a, TextRun $b) => $a->left <=> $b->left,
                );

                $left = min(array_map(fn (TextRun $run) => $run->left, $cellRuns));
                $top = min(array_map(fn (TextRun $run) => $run->top, $cellRuns));
                $right = max(array_map(fn (TextRun $run) => $run->right(), $cellRuns));
                $bottom = max(array_map(fn (TextRun $run) => $run->bottom(), $cellRuns));

                $cells[] = new TableCell(
                    row: (int) $row,
                    column: (int) $column,
                    text: trim(implode(' ', array_map(
                        fn (TextRun $run) => $run->text,
                        $cellRuns,
                    ))),
                    left: $left,
                    top: $top,
                    width: $right - $left,
                    height: $bottom - $top,
                );
            }
        }

        return $cells;
    }

    private function nearestIndex(int $value, array $guides): int
    {
        $bestIndex = 0;
        $bestDistance = PHP_INT_MAX;

        foreach ($guides as $index => $guide) {
            $distance = abs($value - $guide);

            if ($distance < $bestDistance) {
                $bestIndex = (int) $index;
                $bestDistance = $distance;
            }
        }

        return $bestIndex;
    }
}
