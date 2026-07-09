<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Engineering\Module\Electrical;

use App\DeviceScan\Processing\Tables\Canonical\TableHeaderAnalysis;
use App\DeviceScan\Processing\Tables\DTO\TableCell;
use App\DeviceScan\Processing\Tables\DTO\TableGrid;

final class ModuleElectricalRowAnalyzer
{
    public function analyze(
        TableGrid $grid,
        TableHeaderAnalysis $header,
        ModuleElectricalParameterAnalysis $parameters,
    ): ModuleElectricalRowAnalysis {
        $stcRows = [];
        $noctRows = [];
        $temperatureRows = [];
        $ignoredRows = [];
        $decisions = [];

        foreach ($header->dataRows as $rowIndex) {
            $canonical = $parameters->parameterForRow($rowIndex);
            $rowText = strtolower($this->rowText($grid, $rowIndex));

            if ($canonical === null) {
                $ignoredRows[] = $rowIndex;
                $decisions[$rowIndex] = 'ignored_no_parameter';
                continue;
            }

            if ($this->isTemperatureRow($canonical, $rowText)) {
                $temperatureRows[] = $rowIndex;
                $decisions[$rowIndex] = 'temperature_coefficient';
                continue;
            }

            if ($canonical === 'nominal_operating_cell_temperature') {
                $noctRows[] = $rowIndex;
                $decisions[$rowIndex] = 'noct';
                continue;
            }

            if ($this->isStcParameter($canonical)) {
                $stcRows[] = $rowIndex;
                $decisions[$rowIndex] = 'stc';
                continue;
            }

            $ignoredRows[] = $rowIndex;
            $decisions[$rowIndex] = 'ignored_unsupported_parameter';
        }

        return new ModuleElectricalRowAnalysis(
            stcRows: $stcRows,
            noctRows: $noctRows,
            temperatureCoefficientRows: $temperatureRows,
            ignoredRows: $ignoredRows,
            metadata: [
                'source' => self::class,
                'decisions' => $decisions,
            ],
        );
    }

    private function isStcParameter(string $canonical): bool
    {
        return in_array($canonical, [
            'rated_max_power',
            'open_circuit_voltage',
            'maximum_power_voltage',
            'short_circuit_current',
            'maximum_power_current',
            'module_efficiency',
        ], true);
    }

    private function isTemperatureRow(string $canonical, string $rowText): bool
    {
        return str_starts_with($canonical, 'temperature_coefficient_')
            || str_contains($rowText, 'temperature coefficient')
            || str_contains($rowText, 'coefficients of')
            || str_contains($rowText, 'coefficient of')
            || str_contains($rowText, '%/°c')
            || str_contains($rowText, '%/℃');
    }

    private function rowText(TableGrid $grid, int $rowIndex): string
    {
        $cells = array_values(array_filter(
            $grid->cells,
            fn (TableCell $cell) => $cell->row === $rowIndex,
        ));

        usort($cells, fn (TableCell $a, TableCell $b) => $a->column <=> $b->column);

        return trim(implode(' ', array_map(fn (TableCell $cell) => $cell->text, $cells)));
    }
}