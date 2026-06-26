<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing;

use App\DeviceScan\Datasheets\Datasheet;
use App\DeviceScan\Datasheets\DatasheetCell;
use App\DeviceScan\Datasheets\DatasheetModelGroup;
use App\DeviceScan\Datasheets\DatasheetRow;
use App\DeviceScan\Datasheets\DatasheetTable;
use App\DeviceScan\Processing\Document\SourceDocument;
use App\DeviceScan\Processing\Tables\DetectedTable;
use App\DeviceScan\Processing\Tables\DetectedTableCell;
use App\DeviceScan\Processing\Tables\DetectedTableRow;

final class DatasheetAssembler
{
    public function assemble(SourceDocument $document, string $deviceType): Datasheet
    {
        $tables = $this->collectTables($document);

        return new Datasheet(
            deviceType: $deviceType,
            title: pathinfo($document->filename, PATHINFO_FILENAME),
            pageCount: $document->pageCount,
            status: 'parsed',
            modelGroups: [
                new DatasheetModelGroup(
                    name: 'Main',
                    models: $this->collectModels($tables),
                    tables: array_map(
                        fn (DetectedTable $table) => $this->assembleTable($table),
                        $tables,
                    ),
                ),
            ],
            metadata: [
                'filename' => $document->filename,
                'mime_type' => $document->mimeType,
                'warnings' => $document->warnings,
                'table_count' => count($tables),
            ],
        );
    }

    /**
     * @return DetectedTable[]
     */
    private function collectTables(SourceDocument $document): array
    {
        $tables = [];

        foreach ($document->pages as $page) {
            foreach ($page->tables as $table) {
                if ($table instanceof DetectedTable) {
                    $tables[] = $table;
                }
            }
        }

        return $tables;
    }

    /**
     * @param DetectedTable[] $tables
     * @return string[]
     */
    private function collectModels(array $tables): array
    {
        $models = [];

        foreach ($tables as $table) {
            foreach ($table->models as $model) {
                $models[] = $model;
            }
        }

        return array_values(array_unique($models));
    }

    private function assembleTable(DetectedTable $table): DatasheetTable
    {
        return new DatasheetTable(
            title: $table->title,
            rows: array_map(
                fn (DetectedTableRow $row) => $this->assembleRow($row),
                $table->rows,
            ),
        );
    }

    private function assembleRow(DetectedTableRow $row): DatasheetRow
    {
        return new DatasheetRow(
            label: $row->label,
            cells: array_map(
                fn (DetectedTableCell $cell) => $this->assembleCell($cell),
                $row->cells,
            ),
        );
    }

    private function assembleCell(DetectedTableCell $cell): DatasheetCell
    {
        return new DatasheetCell(
            value: $cell->value,
            displayValue: $cell->displayValue,
            unit: $cell->unit,
        );
    }
}