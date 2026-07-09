<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Engineering\Module\Electrical;

use App\DeviceScan\Processing\Tables\Canonical\TableHeaderAnalysis;
use App\DeviceScan\Processing\Tables\DTO\TableCell;
use App\DeviceScan\Processing\Tables\DTO\TableGrid;

final class ModuleElectricalParameterAnalyzer
{
    public function analyze(TableGrid $grid, TableHeaderAnalysis $header): ModuleElectricalParameterAnalysis
    {
        $rows = [];

        foreach ($header->dataRows as $rowIndex) {

            $sourceText = $this->parameterText($grid, $header, $rowIndex);

            $canonical = $this->resolveCanonical($sourceText);

            if ($canonical === null) {
                continue;
            }

            $rows[$rowIndex] = [
                'canonical' => $canonical['canonical'],
                'confidence' => $canonical['confidence'],
                'method' => $canonical['method'],
                'source_text' => $sourceText,
            ];
        }

        return new ModuleElectricalParameterAnalysis(
            rows: $rows,
            metadata: [
                'source' => self::class,
            ],
        );
    }

    private function parameterText(
        TableGrid $grid,
        TableHeaderAnalysis $header,
        int $rowIndex,
    ): string {

        $cells = $this->cellsInRow($grid, $rowIndex);

        if ($header->parameterColumn !== null) {

            $leftCells = array_values(array_filter(
                $cells,
                fn (TableCell $cell) => $cell->column <= $header->parameterColumn + 4,
            ));

            $text = trim(implode(' ', array_map(
                fn (TableCell $cell) => $cell->text,
                $leftCells,
            )));

            if ($text !== '') {
                return $text;
            }
        }

        return trim(implode(' ', array_map(
            fn (TableCell $cell) => $cell->text,
            $cells,
        )));
    }

    /**
     * @return array{
     *      canonical:string,
     *      confidence:float,
     *      method:string
     * }|null
     */
    private function resolveCanonical(string $text): ?array
    {
        $clean = $this->clean($text);

        /*
        |--------------------------------------------------------------------------
        | First pass: engineering abbreviations (highest confidence)
        |--------------------------------------------------------------------------
        */

        $abbreviations = [
            'vmp' => 'maximum_power_voltage',
            'vmpp' => 'maximum_power_voltage',

            'imp' => 'maximum_power_current',
            'impp' => 'maximum_power_current',

            'voc' => 'open_circuit_voltage',
            'isc' => 'short_circuit_current',

            'pmax' => 'rated_max_power',
            'pmpp' => 'rated_max_power',
        ];

        foreach ($abbreviations as $abbr => $canonical) {

            if (preg_match('/\b'.preg_quote($abbr, '/').'\b/u', $clean)) {
                return [
                    'canonical' => $canonical,
                    'confidence' => 1.0,
                    'method' => 'abbreviation',
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Second pass: phrase matching
        |--------------------------------------------------------------------------
        */

        $rules = [

            'temperature_coefficient_pmax' => [
                'temperature coefficients of pmax',
                'temperature coefficient of pmax',
                'coefficient of pmax',
                'pmax coefficient',
            ],

            'temperature_coefficient_voc' => [
                'temperature coefficients of voc',
                'temperature coefficient of voc',
                'coefficient of voc',
                'voc coefficient',
            ],

            'temperature_coefficient_isc' => [
                'temperature coefficients of isc',
                'temperature coefficient of isc',
                'coefficient of isc',
                'isc coefficient',
            ],

            'maximum_power_voltage' => [
                'maximum power voltage',
                'voltage at maximum power',
            ],

            'maximum_power_current' => [
                'maximum power current',
                'current at maximum power',
            ],

            'open_circuit_voltage' => [
                'open circuit voltage',
                'open-circuit voltage',
            ],

            'short_circuit_current' => [
                'short circuit current',
                'short-circuit current',
            ],

            'rated_max_power' => [
                'rated maximum power',
                'maximum power',
                'max power',
            ],

            'module_efficiency' => [
                'module efficiency',
                'efficiency',
            ],

            'nominal_operating_cell_temperature' => [
                'nominal operating cell temperature',
                'noct',
            ],

            'maximum_system_voltage' => [
                'maximum system voltage',
                'system voltage',
            ],

            'maximum_series_fuse_rating' => [
                'maximum series fuse rating',
                'series fuse rating',
                'fuse rating',
            ],

            'power_tolerance' => [
                'power tolerance',
            ],
        ];

        foreach ($rules as $canonical => $phrases) {

            foreach ($phrases as $phrase) {

                if (str_contains($clean, $this->clean($phrase))) {

                    return [
                        'canonical' => $canonical,
                        'confidence' => 0.95,
                        'method' => 'phrase',
                    ];
                }
            }
        }

        return null;
    }

    private function clean(string $text): string
    {
        $text = strtolower($text);

        // Keep bracket contents (Vmp), (Imp), (Voc), etc.
        $text = str_replace(
            ['(', ')', '[', ']'],
            ' ',
            $text,
        );

        $text = preg_replace('/[^a-z0-9°℃%\/\s+\-]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return TableCell[]
     */
    private function cellsInRow(TableGrid $grid, int $rowIndex): array
    {
        $cells = array_values(array_filter(
            $grid->cells,
            fn (TableCell $cell) => $cell->row === $rowIndex,
        ));

        usort(
            $cells,
            fn (TableCell $a, TableCell $b) => $a->column <=> $b->column,
        );

        return $cells;
    }
}