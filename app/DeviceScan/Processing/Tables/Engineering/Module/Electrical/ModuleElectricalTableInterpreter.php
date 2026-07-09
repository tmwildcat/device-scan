<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Engineering\Module\Electrical;

use App\DeviceScan\Processing\Tables\Canonical\CanonicalTableAnalyzer;
use App\DeviceScan\Processing\Tables\Canonical\TableHeaderAnalyzer;
use App\DeviceScan\Processing\Tables\DTO\TableCell;
use App\DeviceScan\Processing\Tables\DTO\TableGrid;
use App\DeviceScan\Processing\Tables\Engineering\Common\EngineeringTable;
use App\DeviceScan\Processing\Tables\Engineering\Common\EngineeringTableRow;

final class ModuleElectricalTableInterpreter
{
    public function __construct(
        private readonly TableHeaderAnalyzer $tableHeaderAnalyzer,
        private readonly CanonicalTableAnalyzer $canonicalTableAnalyzer,
        private readonly ModuleElectricalParameterAnalyzer $parameterAnalyzer,
        private readonly ModuleElectricalRowAnalyzer $rowAnalyzer,
    ) {}

    public function interpret(TableGrid $grid): ?EngineeringTable
    {
        
        $analysis = $this->canonicalTableAnalyzer->analyze($grid);

        if (! $analysis->isSupported || $analysis->canonicalType !== 'module_electrical_stc') {
            return null;
        }

        $header = $this->tableHeaderAnalyzer->analyze($grid);

        if ($header->parameterColumn === null || $header->valueColumns === []) {
            return null;
        }

        $parameterAnalysis = $this->parameterAnalyzer->analyze($grid, $header);
        $rowAnalysis = $this->rowAnalyzer->analyze($grid, $header, $parameterAnalysis);
       
        

        $models = $header->modelsByColumn;
        $rows = [];

        foreach ($rowAnalysis->stcRows as $rowIndex) {
    $parameter = $parameterAnalysis->parameterForRow($rowIndex);

    if ($parameter === null) {
        continue;
    }

    $parameterCell = $this->cellAt($grid, $rowIndex, $header->parameterColumn)
        ?? $this->firstCellInRow($grid, $rowIndex);

    if ($parameterCell === null) {
        continue;
    }

    $values = [];

    foreach ($header->valueColumns as $column) {
        $cell = $this->cellAt($grid, $rowIndex, $column);

        if ($cell === null) {
            continue;
        }

        $model = $models[$column] ?? 'col_'.$column;
        $values[$model] = $this->cleanValue($cell->text);
    }

    $rows[] = new EngineeringTableRow(
        parameter: $parameter,
        unit: $this->extractUnit($parameterCell->text),
        values: $values,
        metadata: [
            'source_row' => $rowIndex,
            'parameter_cell' => $parameterCell->toArray(),
            'parameter_analysis' => $parameterAnalysis->rows[$rowIndex] ?? null,
        ],
    );
}

        if ($rows === []) {
            return null;
        }

        return new EngineeringTable(
            type: $grid->type,
            models: array_values(array_unique($models)),
            rows: $rows,
            metadata: [
                'source' => self::class,
                'table_header_analysis' => $header->toArray(),
                'canonical_table_analysis' => $analysis->toArray(),
                'module_electrical_parameter_analysis' => $parameterAnalysis->toArray(),
                'module_electrical_row_analysis' => $rowAnalysis->toArray(),
            ],
        );
    }

    private function firstCellInRow(TableGrid $grid, int $row): ?TableCell
    {
        $cells = array_values(array_filter(
            $grid->cells,
            fn (TableCell $cell) => $cell->row === $row,
        ));

        if ($cells === []) {
            return null;
        }

        usort($cells, fn (TableCell $a, TableCell $b) => $a->column <=> $b->column);

        return $cells[0];
    }

    private function cellAt(TableGrid $grid, int $row, int $column): ?TableCell
    {
        foreach ($grid->cells as $cell) {
            if ($cell->row === $row && $cell->column === $column) {
                return $cell;
            }
        }

        return null;
    }

    private function cleanValue(string $text): string
    {
        return trim($text);
    }

    private function extractUnit(string $text): ?string
    {
        if (preg_match('/\[(W|Wp|V|A|%|°C|℃)\]/u', $text, $match)) {
            return $match[1];
        }

        if (preg_match('/\b(Wp|W|V|A|%)\b/u', $text, $match)) {
            return $match[1];
        }

        return null;
    }
}