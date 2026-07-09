<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Canonical;

use App\DeviceScan\Processing\Tables\DTO\TableCell;
use App\DeviceScan\Processing\Tables\DTO\TableGrid;

final class CanonicalGridDebugAnnotator
{
    public function __construct(
        private readonly ParameterNormalizer $parameterNormalizer,
        private readonly HeaderDetector $headerDetector,
    ) {}

    public function annotate(TableGrid $grid): TableGrid
    {
        $cells = array_map(
            fn (TableCell $cell) => $this->annotateCell($cell),
            $grid->cells,
        );

        $headerDetection = $this->headerDetector->detect(
            new TableGrid(
                type: $grid->type,
                columns: $grid->columns,
                rows: $grid->rows,
                cells: $cells,
                headerDetection: $grid->headerDetection,
                metadata: $grid->metadata,
            )
        );

        return new TableGrid(
            type: $grid->type,
            columns: $grid->columns,
            rows: $grid->rows,
            cells: $cells,
            headerDetection: $headerDetection,
            metadata: [
                ...$grid->metadata,
                'canonical_debug' => true,
            ],
        );
    }

    private function annotateCell(TableCell $cell): TableCell
    {
        $parameter = $this->parameterNormalizer->normalize($cell->text);

        return new TableCell(
            row: $cell->row,
            column: $cell->column,
            text: $cell->text,
            left: $cell->left,
            top: $cell->top,
            width: $cell->width,
            height: $cell->height,
            metadata: [
                ...$cell->metadata,
                'canonical_parameter' => $parameter,
            ],
            ocrText: $cell->ocrText,
            nativeText: $cell->nativeText,
            textSource: $cell->textSource,
        );
    }
}