<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Canonical;

use App\DeviceScan\Processing\Tables\DTO\TableGrid;

use App\DeviceScan\Processing\Tables\Engineering\Module\Electrical\ModuleElectricalTableInterpreter;

final class CanonicalTableAnalyzer
{
    public function __construct(
        private readonly TableHeaderAnalyzer $tableHeaderAnalyzer,
    ) {}

    public function analyze(TableGrid $grid): CanonicalTableAnalysis
    {
        $header = $this->tableHeaderAnalyzer->analyze($grid);

        $score = 0;
        $reasons = [];

        $canonicalCounts = $this->canonicalCounts($grid);
        $canonicalTotal = array_sum($canonicalCounts);

        if ($canonicalTotal >= 3) {
            $score += 30;
            $reasons[] = 'has_at_least_3_canonical_parameters';
        }

        if (count($header->modelsByColumn) >= 3) {
            $score += 25;
            $reasons[] = 'has_at_least_3_model_columns';
        }

        foreach ([
            'rated_max_power',
            'open_circuit_voltage',
            'maximum_power_voltage',
            'short_circuit_current',
            'maximum_power_current',
            'module_efficiency',
        ] as $parameter) {
            if (($canonicalCounts[$parameter] ?? 0) > 0) {
                $score += 10;
                $reasons[] = 'contains_'.$parameter;
            }
        }

        $text = strtolower($this->gridText($grid));

        if (str_contains($text, 'stc')) {
            $score += 10;
            $reasons[] = 'contains_stc';
        }

        if (str_contains($text, 'noct')) {
            $score += 5;
            $reasons[] = 'contains_noct';
        }

        foreach ([
            'current-voltage',
            'current voltage',
            'power-voltage',
            'power voltage',
            'curve',
            'curves',
            'irradiance',
            '1000w/m',
            '800w/m',
            '600w/m',
            '400w/m',
            '200w/m',
        ] as $negative) {
            if (str_contains($text, $negative)) {
                $score -= 15;
                $reasons[] = 'penalty_'.$negative;
            }
        }

        if (str_contains($text, 'packaging')) {
            $score -= 20;
            $reasons[] = 'penalty_packaging';
        }

        if (str_contains($text, 'warranty')) {
            $score -= 20;
            $reasons[] = 'penalty_warranty';
        }

        $canonicalType = $this->canonicalType($canonicalCounts, $text, $score);

        $isSupported = $score >= 45
            && $canonicalType === 'module_electrical_stc';

        return new CanonicalTableAnalysis(
            isSupported: $isSupported,
            canonicalType: $canonicalType,
            confidence: max(0.0, min(1.0, $score / 100)),
            score: $score,
            recommendedInterpreter: $isSupported ? ModuleElectricalTableInterpreter::class : null,
            reasons: $reasons,
            metadata: [
                'header' => $header->toArray(),
                'canonical_counts' => $canonicalCounts,
            ],
        );
    }

    private function canonicalType(array $canonicalCounts, string $text, int $score): string
    {
        $electricalSignals = 0;

        foreach ([
            'rated_max_power',
            'open_circuit_voltage',
            'maximum_power_voltage',
            'short_circuit_current',
            'maximum_power_current',
            'module_efficiency',
        ] as $parameter) {
            if (($canonicalCounts[$parameter] ?? 0) > 0) {
                $electricalSignals++;
            }
        }

        if ($electricalSignals >= 3) {
            return 'module_electrical_stc';
        }

        if (
            str_contains($text, 'current-voltage')
            || str_contains($text, 'power-voltage')
            || str_contains($text, 'curve')
        ) {
            return 'curve_legend';
        }

        if (str_contains($text, 'packaging')) {
            return 'module_packaging';
        }

        if (str_contains($text, 'warranty')) {
            return 'warranty';
        }

        return $score > 0 ? 'engineering_unknown' : 'unknown';
    } 

    private function canonicalCounts(TableGrid $grid): array
    {
        $counts = [];

        foreach ($grid->cells as $cell) {
            $canonical = $cell->metadata['canonical_parameter'] ?? null;

            if (! is_string($canonical) || $canonical === '') {
                continue;
            }

            $counts[$canonical] = ($counts[$canonical] ?? 0) + 1;
        }

        return $counts;
    }

    private function gridText(TableGrid $grid): string
    {
        return trim(implode(' ', array_map(
            fn ($cell) => $cell->text,
            $grid->cells,
        )));
    }
}