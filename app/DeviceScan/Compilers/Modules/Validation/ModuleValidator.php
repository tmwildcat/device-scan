<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\Validation;

use App\DeviceScan\Compilers\Modules\DTO\ModuleDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleElectricalModelDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleSourceValueDto;

final class ModuleValidator
{
    public function validate(ModuleDto $module): ModuleValidationResult
    {
        $issues = [];

        $this->validateElectricalStc($module, $issues);
        $this->validateOperatingAndTemperature($module, $issues);
        $this->validateMechanical($module, $issues);
        $this->validateWarranty($module, $issues);
        $this->validateCompleteness($module, $issues);

        return new ModuleValidationResult($issues);
    }

    /**
     * @param ModuleValidationIssue[] $issues
     */
    private function validateElectricalStc(ModuleDto $module, array &$issues): void
    {
        $models = $module->electricalStc?->models ?? [];

        foreach ($models as $model) {
            $fields = [
                'rated_max_power_w' => $model->ratedMaxPowerW,
                'open_circuit_voltage_v' => $model->openCircuitVoltageV,
                'maximum_power_voltage_v' => $model->maximumPowerVoltageV,
                'short_circuit_current_a' => $model->shortCircuitCurrentA,
                'maximum_power_current_a' => $model->maximumPowerCurrentA,
                'module_efficiency_percent' => $model->moduleEfficiencyPercent,
            ];

            foreach ($fields as $field => $value) {
                $number = $this->number($value);

                if ($value !== null && ($number === null || $number <= 0)) {
                    $issues[] = $this->issue('error', 'module_stc_non_positive_value', 'STC electrical values must be positive.', $this->modelLabel($model), $field, $value);
                }
            }

            $voc = $this->number($model->openCircuitVoltageV);
            $vmp = $this->number($model->maximumPowerVoltageV);
            $isc = $this->number($model->shortCircuitCurrentA);
            $imp = $this->number($model->maximumPowerCurrentA);
            $pmax = $this->number($model->ratedMaxPowerW);
            $efficiency = $this->number($model->moduleEfficiencyPercent);

            if ($voc !== null && $vmp !== null && $voc <= $vmp) {
                $issues[] = $this->issue('error', 'module_stc_voc_not_greater_than_vmp', 'Open-circuit voltage should be greater than maximum power voltage.', $this->modelLabel($model), 'open_circuit_voltage_v', $model->openCircuitVoltageV);
            }

            if ($isc !== null && $imp !== null && $isc <= $imp) {
                $issues[] = $this->issue('error', 'module_stc_isc_not_greater_than_imp', 'Short-circuit current should be greater than maximum power current.', $this->modelLabel($model), 'short_circuit_current_a', $model->shortCircuitCurrentA);
            }

            if ($pmax !== null && $vmp !== null && $imp !== null) {
                $calculated = $vmp * $imp;
                $relativeError = $pmax > 0 ? abs($pmax - $calculated) / $pmax : 1.0;

                if ($relativeError > 0.08) {
                    $issues[] = new ModuleValidationIssue(
                        severity: 'warning',
                        code: 'module_stc_pmax_vmp_imp_mismatch',
                        message: 'Pmax differs from Vmp x Imp by more than 8%.',
                        model: $this->modelLabel($model),
                        field: 'rated_max_power_w',
                        value: $pmax,
                        context: [
                            'calculated_pmax_w' => round($calculated, 3),
                            'relative_error' => round($relativeError, 4),
                            'source' => $this->sourceContext($model->ratedMaxPowerW),
                        ],
                    );
                }
            }

            if ($efficiency !== null && ($efficiency < 10 || $efficiency > 30)) {
                $issues[] = $this->issue('warning', 'module_stc_efficiency_outside_expected_range', 'Module efficiency is outside the usual 10% to 30% range.', $this->modelLabel($model), 'module_efficiency_percent', $model->moduleEfficiencyPercent);
            }

            if (! $this->isThinFilm($module, $model) && $voc !== null && ($voc < 20 || $voc > 80)) {
                $issues[] = $this->issue('warning', 'module_stc_csi_voc_outside_expected_range', 'c-Si module Voc is outside the usual 20V to 80V range.', $this->modelLabel($model), 'open_circuit_voltage_v', $model->openCircuitVoltageV);
            }

            foreach ([
                'short_circuit_current_a' => $model->shortCircuitCurrentA,
                'maximum_power_current_a' => $model->maximumPowerCurrentA,
            ] as $field => $value) {
                $number = $this->number($value);

                if ($number !== null && $number > 25) {
                    $issues[] = $this->issue('warning', 'module_stc_current_high_for_current_corpus', 'Module current is higher than expected for the current corpus.', $this->modelLabel($model), $field, $value);
                }
            }
        }

        $pmaxValues = [];

        foreach ($models as $model) {
            $pmax = $this->number($model->ratedMaxPowerW);

            if ($pmax === null) {
                continue;
            }

            $pmaxValues[] = [$pmax, $model];
        }

        if (count($pmaxValues) > 2 && ! $this->isMonotonic(array_column($pmaxValues, 0))) {
            $last = end($pmaxValues);
            $issues[] = $this->issue('warning', 'module_stc_watt_classes_not_monotonic', 'Consecutive watt classes are not consistently ordered.', $this->modelLabel($last[1]), 'rated_max_power_w', $last[1]->ratedMaxPowerW);
        }
    }

    /**
     * @param ModuleValidationIssue[] $issues
     */
    private function validateOperatingAndTemperature(ModuleDto $module, array &$issues): void
    {
        $operating = $module->operatingConditions;
        $temperature = $module->temperatureCharacteristics;

        $systemVoltage = $this->number($operating?->maximumSystemVoltage);
        if ($systemVoltage !== null && ! in_array((int) round($systemVoltage), [1000, 1500], true)) {
            $issues[] = $this->issue('warning', 'module_operating_unusual_system_voltage', 'Maximum system voltage is not the usual 1000V or 1500V value.', null, 'maximum_system_voltage', $operating?->maximumSystemVoltage);
        }

        $fuse = $this->number($operating?->maximumSeriesFuseRating);
        if ($fuse !== null) {
            if ($fuse <= 0) {
                $issues[] = $this->issue('error', 'module_operating_non_positive_fuse_rating', 'Maximum series fuse rating must be positive.', null, 'maximum_series_fuse_rating', $operating?->maximumSeriesFuseRating);
            } elseif ($fuse < 5 || $fuse > 40) {
                $issues[] = $this->issue('warning', 'module_operating_fuse_rating_outside_expected_range', 'Maximum series fuse rating is outside the usual 5A to 40A range.', null, 'maximum_series_fuse_rating', $operating?->maximumSeriesFuseRating);
            }
        }

        $noct = $this->number($temperature?->nominalOperatingCellTemperature);
        if ($noct !== null && ($noct < 35 || $noct > 55)) {
            $issues[] = $this->issue('warning', 'module_temperature_noct_outside_expected_range', 'NOCT/NMOT is outside the usual 35C to 55C range.', null, 'nominal_operating_cell_temperature', $temperature?->nominalOperatingCellTemperature);
        }

        $pmaxCoefficient = $this->number($temperature?->temperatureCoefficientPmax);
        if ($pmaxCoefficient !== null && $pmaxCoefficient >= 0) {
            $issues[] = $this->issue('warning', 'module_temperature_pmax_coefficient_not_negative', 'Temperature coefficient of Pmax is usually negative.', null, 'temperature_coefficient_pmax', $temperature?->temperatureCoefficientPmax);
        }

        $vocCoefficient = $this->number($temperature?->temperatureCoefficientVoc);
        if ($vocCoefficient !== null && $vocCoefficient >= 0) {
            $issues[] = $this->issue('warning', 'module_temperature_voc_coefficient_not_negative', 'Temperature coefficient of Voc is usually negative.', null, 'temperature_coefficient_voc', $temperature?->temperatureCoefficientVoc);
        }

        $iscCoefficient = $this->number($temperature?->temperatureCoefficientIsc);
        if ($iscCoefficient !== null && $iscCoefficient < -0.02) {
            $issues[] = $this->issue('warning', 'module_temperature_isc_coefficient_negative', 'Temperature coefficient of Isc is usually positive or near zero.', null, 'temperature_coefficient_isc', $temperature?->temperatureCoefficientIsc);
        }

        $range = $this->temperatureRange($operating?->operatingTemperature);
        if ($operating?->operatingTemperature !== null && $range !== null && ($range[0] >= 0 || $range[1] <= 0)) {
            $issues[] = $this->issue('warning', 'module_operating_temperature_range_unusual', 'Operating temperature range should include a negative lower bound and positive upper bound.', null, 'operating_temperature', $operating->operatingTemperature);
        }
    }

    /**
     * @param ModuleValidationIssue[] $issues
     */
    private function validateMechanical(ModuleDto $module, array &$issues): void
    {
        $mechanical = $module->mechanical;

        foreach ([
            'length_mm' => [$mechanical?->lengthMm, 1500, 2600],
            'width_mm' => [$mechanical?->widthMm, 900, 1400],
            'thickness_mm' => [$mechanical?->thicknessMm, 25, 50],
            'weight_kg' => [$mechanical?->weightKg, 15, 45],
        ] as $field => [$value, $min, $max]) {
            $number = $this->number($value);

            if ($value !== null && ($number === null || $number <= 0)) {
                $issues[] = $this->issue('error', 'module_mechanical_non_positive_value', 'Mechanical numeric values must be positive.', null, $field, $value);
                continue;
            }

            if ($number !== null && ($number < $min || $number > $max)) {
                $issues[] = $this->issue('warning', 'module_mechanical_value_outside_plausible_range', 'Mechanical value is outside the current plausible module range.', null, $field, $value);
            }
        }

        $cellCount = $this->number($mechanical?->cellCount);
        if ($mechanical?->cellCount !== null && ($cellCount === null || $cellCount <= 0)) {
            $issues[] = $this->issue('error', 'module_mechanical_non_positive_cell_count', 'Cell count must be positive.', null, 'cell_count', $mechanical->cellCount);
        }

        $diodes = $this->number($mechanical?->bypassDiodes);
        if ($diodes !== null && $diodes < 0) {
            $issues[] = $this->issue('error', 'module_mechanical_negative_bypass_diodes', 'Bypass diode count cannot be negative.', null, 'bypass_diodes', $mechanical?->bypassDiodes);
        }
    }

    /**
     * @param ModuleValidationIssue[] $issues
     */
    private function validateWarranty(ModuleDto $module, array &$issues): void
    {
        $warranty = $module->warranty;

        foreach ([
            'product_warranty_years' => [$warranty?->productWarrantyYears, 10, 30],
            'linear_power_warranty_years' => [$warranty?->linearPowerWarrantyYears, 20, 35],
            'first_year_degradation_percent' => [$warranty?->firstYearDegradationPercent, 0, 3],
            'annual_degradation_percent' => [$warranty?->annualDegradationPercent, 0, 1],
            'end_of_warranty_output_percent' => [$warranty?->endOfWarrantyOutputPercent, 80, 95],
        ] as $field => [$value, $min, $max]) {
            $number = $this->number($value);

            if ($number !== null && ($number < $min || $number > $max)) {
                $issues[] = $this->issue('warning', 'module_warranty_value_outside_expected_range', 'Warranty value is outside the current expected range.', null, $field, $value);
            }
        }
    }

    /**
     * @param ModuleValidationIssue[] $issues
     */
    private function validateCompleteness(ModuleDto $module, array &$issues): void
    {
        foreach ($module->electricalStc?->models ?? [] as $model) {
            foreach ([
                'rated_max_power_w' => $model->ratedMaxPowerW,
                'open_circuit_voltage_v' => $model->openCircuitVoltageV,
                'maximum_power_voltage_v' => $model->maximumPowerVoltageV,
                'short_circuit_current_a' => $model->shortCircuitCurrentA,
                'maximum_power_current_a' => $model->maximumPowerCurrentA,
                'module_efficiency_percent' => $model->moduleEfficiencyPercent,
            ] as $field => $value) {
                if ($value === null) {
                    $issues[] = new ModuleValidationIssue(
                        severity: 'warning',
                        code: 'module_stc_model_incomplete',
                        message: 'STC model is missing a required electrical field.',
                        model: $this->modelLabel($model),
                        field: $field,
                    );
                }
            }
        }

        foreach ([
            'mechanical' => $module->mechanical,
            'operating_conditions' => $module->operatingConditions,
            'temperature_characteristics' => $module->temperatureCharacteristics,
            'warranty' => $module->warranty,
        ] as $field => $block) {
            if ($block === null) {
                $issues[] = new ModuleValidationIssue(
                    severity: 'warning',
                    code: 'module_engineering_block_missing',
                    message: 'Expected module engineering block was not extracted.',
                    field: $field,
                );
            }
        }
    }

    private function isThinFilm(ModuleDto $module, ModuleElectricalModelDto $model): bool
    {
        $haystack = mb_strtolower(implode(' ', array_filter([
            $module->manufacturer,
            $module->technology,
            $module->series,
            $model->modelSeries,
        ])));

        return str_contains($haystack, 'first solar')
            || str_contains($haystack, 'thin')
            || ($model->modelSeries !== null && str_starts_with(mb_strtolower($model->modelSeries), 'fs-'));
    }

    private function modelLabel(ModuleElectricalModelDto $model): string
    {
        return $model->displayName
            ?? $model->modelSeries
            ?? ($model->powerClassW !== null ? ((int) round($model->powerClassW)).'W' : 'unknown');
    }

    /**
     * @param float[] $values
     */
    private function isMonotonic(array $values): bool
    {
        $ascending = true;
        $descending = true;

        for ($index = 1; $index < count($values); $index++) {
            if ($values[$index] < $values[$index - 1]) {
                $ascending = false;
            }

            if ($values[$index] > $values[$index - 1]) {
                $descending = false;
            }
        }

        return $ascending || $descending;
    }

    private function issue(
        string $severity,
        string $code,
        string $message,
        ?string $model,
        ?string $field,
        ?ModuleSourceValueDto $value,
    ): ModuleValidationIssue {
        return new ModuleValidationIssue(
            severity: $severity,
            code: $code,
            message: $message,
            model: $model,
            field: $field,
            value: $value?->value,
            context: [
                'source' => $this->sourceContext($value),
            ],
        );
    }

    private function sourceContext(?ModuleSourceValueDto $value): array
    {
        if ($value === null) {
            return [];
        }

        return array_filter([
            'page' => $value->sourcePage,
            'section' => $value->sourceSection,
            'source_text' => $value->sourceText,
            'confidence' => $value->confidence,
        ], fn ($item) => $item !== null && $item !== '');
    }

    private function number(?ModuleSourceValueDto $value): ?float
    {
        $raw = $value?->normalizedValue ?? $value?->value;

        if (is_int($raw) || is_float($raw)) {
            return (float) $raw;
        }

        if (! is_string($raw) || ! preg_match('/[-+]?\d+(?:[.,]\d+)?/u', $raw, $match)) {
            return null;
        }

        return (float) str_replace(',', '.', $match[0]);
    }

    /**
     * @return array{0:float,1:float}|null
     */
    private function temperatureRange(?ModuleSourceValueDto $value): ?array
    {
        $raw = $value?->value;

        if (! is_string($raw)) {
            return null;
        }

        if (! preg_match_all('/[-−]?\s*\d+(?:[.,]\d+)?/u', $raw, $matches) || count($matches[0] ?? []) < 2) {
            return null;
        }

        $lower = (float) str_replace(['−', ',', ' '], ['-', '.', ''], $matches[0][0]);
        $upper = (float) str_replace(['−', ',', ' '], ['-', '.', ''], $matches[0][1]);

        return [$lower, $upper];
    }
}
