<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Native\Analysis;

use App\DeviceScan\Processing\Native\DTO\NativeTextRun;
use App\DeviceScan\Processing\Ocr\OcrBlock;
use App\DeviceScan\Processing\Tables\DTO\TableRegion;

final class NativeTableRegionDetector
{
    /**
     * @param NativeTextRun[] $runs
     * @return TableRegion[]
     */
    public function detect(array $runs): array
    {
        $runs = array_values(array_filter(
            $runs,
            fn (NativeTextRun $run) => trim($run->text) !== ''
        ));

        if (count($runs) < 6) {
            return [];
        }

        usort(
            $runs,
            fn (NativeTextRun $a, NativeTextRun $b) => ($a->top <=> $b->top) ?: ($a->left <=> $b->left)
        );

        $clusters = $this->clusterByVerticalGaps($runs);

        $regions = [];

        foreach ($clusters as $cluster) {
            if (! $this->looksLikeTable($cluster)) {
                continue;
            }

            $regions[] = $this->makeRegion($cluster);
        }

        return $regions;
    }

    /**
     * @param NativeTextRun[] $runs
     * @return array<int, NativeTextRun[]>
     */
    private function clusterByVerticalGaps(array $runs): array
    {
        $clusters = [];
        $current = [];

        foreach ($runs as $run) {
            if ($current === []) {
                $current[] = $run;
                continue;
            }

            $previous = $current[array_key_last($current)];
            $gap = $run->top - $previous->bottom();

            if ($gap <= max(18.0, $previous->height * 2.0)) {
                $current[] = $run;
                continue;
            }

            $clusters[] = $current;
            $current = [$run];
        }

        if ($current !== []) {
            $clusters[] = $current;
        }

        return $clusters;
    }

    /**
     * @param NativeTextRun[] $runs
     */
    private function looksLikeTable(array $runs): bool
    {
        if (count($runs) < 6) {
            return false;
        }

        $rowBuckets = $this->rowBuckets($runs);
        $columnBuckets = $this->columnBuckets($runs);

        if (count($rowBuckets) < 3) {
            return false;
        }

        if (count($columnBuckets) < 2) {
            return false;
        }

        $numericRuns = array_filter(
            $runs,
            fn (NativeTextRun $run) => preg_match('/\d/', $run->text) === 1
        );

        return count($numericRuns) >= 3;
    }

    /**
     * @param NativeTextRun[] $runs
     * @return array<int, NativeTextRun[]>
     */
    private function rowBuckets(array $runs): array
    {
        $rows = [];

        foreach ($runs as $run) {
            $placed = false;
            $centerY = $run->top + ($run->height / 2);

            foreach ($rows as &$row) {
                $rowCenter = $this->averageCenterY($row);

                if (abs($centerY - $rowCenter) <= max(5.0, $run->height * 0.75)) {
                    $row[] = $run;
                    $placed = true;
                    break;
                }
            }

            unset($row);

            if (! $placed) {
                $rows[] = [$run];
            }
        }

        return $rows;
    }

    /**
     * @param NativeTextRun[] $runs
     * @return array<int, NativeTextRun[]>
     */
    private function columnBuckets(array $runs): array
    {
        $columns = [];

        foreach ($runs as $run) {
            $placed = false;

            foreach ($columns as &$column) {
                $avgLeft = array_sum(array_map(
                    fn (NativeTextRun $item) => $item->left,
                    $column
                )) / max(1, count($column));

                if (abs($run->left - $avgLeft) <= 18.0) {
                    $column[] = $run;
                    $placed = true;
                    break;
                }
            }

            unset($column);

            if (! $placed) {
                $columns[] = [$run];
            }
        }

        return array_values(array_filter(
            $columns,
            fn (array $column) => count($column) >= 2
        ));
    }

    /**
     * @param NativeTextRun[] $runs
     */
    private function averageCenterY(array $runs): float
    {
        return array_sum(array_map(
            fn (NativeTextRun $run) => $run->top + ($run->height / 2),
            $runs
        )) / max(1, count($runs));
    }

    /**
     * @param NativeTextRun[] $runs
     */
    private function makeRegion(array $runs): TableRegion
    {
        $left = (int) floor(min(array_map(fn (NativeTextRun $run) => $run->left, $runs)));
        $top = (int) floor(min(array_map(fn (NativeTextRun $run) => $run->top, $runs)));
        $right = (int) ceil(max(array_map(fn (NativeTextRun $run) => $run->right(), $runs)));
        $bottom = (int) ceil(max(array_map(fn (NativeTextRun $run) => $run->bottom(), $runs)));

        $text = trim(implode(' ', array_map(
            fn (NativeTextRun $run) => $run->text,
            $runs
        )));

        $block = new OcrBlock(
            text: $text,
            left: $left,
            top: $top,
            width: max(1, $right - $left),
            height: max(1, $bottom - $top),
            lines: [],
            confidence: null,
            metadata: [
                'synthetic' => true,
                'source' => 'native_table_region_detector',
                'run_count' => count($runs),
            ],
        );

        return new TableRegion(
            type: 'native_table',
            left: $left,
            top: $top,
            width: max(1, $right - $left),
            height: max(1, $bottom - $top),
            block: $block,
            metadata: [
                'source' => 'native_table_region_detector',
                'run_count' => count($runs),
            ],
        );
    }
}