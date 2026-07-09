<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Ocr;

use App\DeviceScan\Processing\Tables\Engineering\Common\EngineeringTable;
use App\DeviceScan\Processing\Tables\DTO\TableGrid;
use App\DeviceScan\Processing\Tables\DTO\TableRegion;

final readonly class OcrResult
{
    /**
     * @param OcrWord[] $words
     * @param OcrLine[] $lines
     * @param OcrBlock[] $blocks
     * @param TableRegion[] $tableRegions
     * @param TableGrid[] $grids
     * @param EngineeringTable[] $engineeringTables
     */
    public function __construct(
        public int $page,
        public array $words = [],
        public array $lines = [],
        public array $blocks = [],
        public array $tableRegions = [],
        public array $grids = [],
        public array $engineeringTables = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'metadata' => $this->metadata,

            'words' => array_map(
                fn (OcrWord $word) => $word->toArray(),
                $this->words,
            ),

            'lines' => array_map(
                fn (OcrLine $line) => $line->toArray(),
                $this->lines,
            ),

            'blocks' => array_map(
                fn (OcrBlock $block) => $block->toArray(),
                $this->blocks,
            ),

            'table_regions' => array_map(
                fn (TableRegion $region) => $region->toArray(),
                $this->tableRegions,
            ),

            'grids' => array_map(
                fn (TableGrid $grid) => $grid->toArray(),
                $this->grids,
            ),

            'engineering_tables' => array_map(
                fn (EngineeringTable $table) => $table->toArray(),
                $this->engineeringTables,
            ),
        ];
    }
}
