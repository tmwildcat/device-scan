<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Native\Analysis;

use App\DeviceScan\Processing\Native\DTO\NativeColumn;
use App\DeviceScan\Processing\Native\DTO\NativeLine;
use App\DeviceScan\Processing\Native\DTO\NativeWord;

final class NativeColumnDetector
{
    /**
     * @param NativeLine[] $lines
     * @return NativeColumn[]
     */
    public function detect(array $lines): array
    {
        $positions = [];

        foreach ($lines as $line) {
            foreach ($line->words as $word) {
                $positions[] = $word->left;
            }
        }

        sort($positions);

        $clusters = [];

        foreach ($positions as $x) {
            if ($clusters === []) {
                $clusters[] = [$x];
                continue;
            }

            $last = array_key_last($clusters);
            $avg = array_sum($clusters[$last]) / count($clusters[$last]);

            if (abs($x - $avg) <= 14.0) {
                $clusters[$last][] = $x;
                continue;
            }

            $clusters[] = [$x];
        }

        $columns = [];

        foreach ($clusters as $index => $cluster) {
            if (count($cluster) < 2) {
                continue;
            }

            $columns[] = new NativeColumn(
                index: count($columns),
                x: array_sum($cluster) / count($cluster),
                metadata: [
                    'source' => self::class,
                    'support' => count($cluster),
                ],
            );
        }

        return $columns;
    }
}