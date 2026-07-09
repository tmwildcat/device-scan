<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Native\Analysis;

use App\DeviceScan\Processing\Native\DTO\NativeTextRun;
use App\DeviceScan\Processing\Tables\DTO\TableGrid;
use App\DeviceScan\Processing\Tables\DTO\TableRegion;

final class NativeGridDetector
{
    public function __construct(
        private readonly NativeLineBuilder $lineBuilder,
        private readonly NativeColumnDetector $columnDetector,
        private readonly NativeGridBuilder $gridBuilder,
    ) {}

    /**
     * @param NativeTextRun[] $runs
     */
    public function detect(TableRegion $region, array $runs): ?TableGrid
    {
        $words = [];

        foreach ($runs as $run) {
            foreach ($run->words as $word) {
                $words[] = $word;
            }
        }

        $lines = $this->lineBuilder->build($words);
        $columns = $this->columnDetector->detect($lines);

        return $this->gridBuilder->build($region, $lines, $columns);
    }
}