<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\Validation;

use App\DeviceScan\Compilers\Inverters\DTO\InverterDto;
use App\DeviceScan\Compilers\Inverters\DTO\InverterElectricalModelDto;
use App\DeviceScan\Compilers\Inverters\DTO\InverterSourceValueDto;

final class InverterValidator
{
    public function validate(InverterDto $dto): InverterValidationResult
    {
        if ($dto->deviceType === 'accessory') {
            return new InverterValidationResult(
                issues: [
                    new InverterValidationIssue(
                        severity: 'info',
                        code: 'accessory_not_validated_as_inverter',
                        message: 'Accessory document was classified outside inverter validation.',
                        value: $dto->unsupportedReason,
                    ),
                ],
                summary: ['skipped' => true],
            );
        }

        $issues = [];
        $this->validateCompleteness($dto, $issues);
        $this->validateDc($dto, $issues);
        $this->validateAc($dto, $issues);
        $this->validateProtection($dto, $issues);
        $this->validateRatedPowerConditions($dto, $issues);

        return new InverterValidationResult(
            issues: $issues,
            summary: [
                'dc_models_checked' => count($dto->dcInput?->models ?? []),
                'ac_models_checked' => count($dto->acOutput?->models ?? []),
                'errors' => count(array_filter($issues, fn (InverterValidationIssue $issue) => $issue->severity === 'error')),
                'warnings' => count(array_filter($issues, fn (InverterValidationIssue $issue) => $issue->severity === 'warning')),
                'infos' => count(array_filter($issues, fn (InverterValidationIssue $issue) => $issue->severity === 'info')),
            ],
        );
    }

    /**
     * @return array{score:int,grade:string,reasons:string[]}
     */
    public function quality(InverterDto $dto, InverterValidationResult $validation): array
    {
        if ($dto->deviceType === 'accessory') {
            return [
                'score' => 0,
                'grade' => 'N/A',
                'reasons' => ['Document classified as accessory; inverter extraction quality is not scored.'],
            ];
        }

        $score = 0;
        $reasons = [];

        $identityScore = 0;
        foreach ([$dto->manufacturer, $dto->series, $dto->modelSeries ?? $dto->modelName, $dto->deviceType] as $part) {
            if ($part !== null && $part !== '' && $part !== 'unknown') {
                $identityScore += 4;
            }
        }
        $identityScore = min(15, $identityScore);
        $score += $identityScore;
        if ($identityScore < 12) {
            $reasons[] = 'Identity is incomplete.';
        }

        $dcScore = $this->blockScore($dto->dcInput?->models ?? [], ['max_dc_voltage', 'mppt_voltage_range', 'max_input_current'], 25);
        $score += $dcScore;
        if ($dcScore < 15) {
            $reasons[] = 'DC input extraction is sparse.';
        }

        $acScore = $this->blockScore($dto->acOutput?->models ?? [], ['rated_ac_power', 'rated_ac_voltage', 'rated_frequency'], 25);
        $score += $acScore;
        if ($acScore < 15) {
            $reasons[] = 'AC output extraction is sparse.';
        }

        $protectionCount = $this->protectionFieldCount($dto);
        $protectionScore = min(15, $protectionCount * 2);
        $score += $protectionScore;
        if ($protectionScore < 6) {
            $reasons[] = 'Protection extraction is limited.';
        }

        $sourceScore = ($dto->sourceMetadata !== [] ? 5 : 0) + ($dto->sections !== [] ? 5 : 0);
        $score += $sourceScore;
        if ($sourceScore < 10) {
            $reasons[] = 'Source metadata or detected sections are incomplete.';
        }

        $penalty = ($validation->countBySeverity('error') * 12) + ($validation->countBySeverity('warning') * 3);
        $score += max(0, 10 - $penalty);
        if ($penalty > 0) {
            $reasons[] = 'Validation issues reduced the quality score.';
        }

        $score = max(0, min(100, $score));

        return [
            'score' => $score,
            'grade' => $this->grade($score),
            'reasons' => $reasons === [] ? ['Extraction is internally consistent for the current v0.4 rules.'] : $reasons,
        ];
    }

    /**
     * @param InverterValidationIssue[] $issues
     */
    private function validateCompleteness(InverterDto $dto, array &$issues): void
    {
        if ($dto->deviceType === null || $dto->deviceType === 'unknown') {
            $issues[] = $this->issue('warning', 'device_type_unknown', 'Device type could not be classified.');
        }

        if (($dto->modelSeries ?? $dto->modelName ?? null) === null && $dto->models === []) {
            $issues[] = $this->issue('warning', 'weak_inverter_identity', 'Model identity is weak or missing.');
        }

        if ($this->allModelsEmpty($dto->dcInput?->models ?? [])) {
            $issues[] = $this->issue('warning', 'missing_dc_input', 'Supported inverter has no DC input extraction.');
        }

        if ($this->allModelsEmpty($dto->acOutput?->models ?? [])) {
            $issues[] = $this->issue('warning', 'missing_ac_output', 'Supported inverter has no AC output extraction.');
        }
    }

    /**
     * @param InverterValidationIssue[] $issues
     */
    private function validateDc(InverterDto $dto, array &$issues): void
    {
        $isCentral = $dto->deviceType === 'central_inverter';
        $requiresMppt = in_array($dto->deviceType, ['string_inverter', 'hybrid_inverter'], true);

        foreach ($dto->dcInput?->models ?? [] as $model) {
            if ($model->fields === []) {
                continue;
            }

            $maxDc = $this->fieldNumber($model, 'max_dc_voltage', prefer: 'max');
            $startup = $this->fieldNumber($model, 'startup_voltage', prefer: 'min');
            $ratedDc = $this->fieldNumber($model, 'rated_dc_voltage', prefer: 'max');
            $mppt = $this->fieldRange($model, 'mppt_voltage_range');
            $inputCurrent = $this->fieldNumber($model, 'max_input_current', prefer: 'max');
            $shortCircuit = $this->fieldNumber($model, 'max_short_circuit_current', prefer: 'max');
            $mpptCount = $this->fieldNumber($model, 'mppt_count', prefer: 'max');
            $strings = $this->fieldNumber($model, 'strings_per_mppt', prefer: 'max');

            if ($maxDc !== null && $maxDc <= 0) {
                $issues[] = $this->fieldIssue('error', 'max_dc_voltage_non_positive', 'Maximum DC voltage must be positive.', $model, 'max_dc_voltage');
            }

            if ($startup !== null) {
                if ($startup <= 0) {
                    $issues[] = $this->fieldIssue('error', 'startup_voltage_non_positive', 'Startup voltage must be positive.', $model, 'startup_voltage');
                } elseif ($maxDc !== null && $startup >= $maxDc) {
                    $issues[] = $this->fieldIssue('warning', 'startup_voltage_not_below_max_dc', 'Startup voltage should be less than maximum DC voltage.', $model, 'startup_voltage');
                }
            }

            if ($ratedDc !== null && $maxDc !== null && $ratedDc >= $maxDc) {
                $issues[] = $this->fieldIssue('warning', 'rated_dc_voltage_not_below_max_dc', 'Rated DC voltage should be below maximum DC voltage.', $model, 'rated_dc_voltage');
            }

            if ($mppt !== null) {
                if ($mppt['min'] >= $mppt['max']) {
                    $issues[] = $this->fieldIssue('warning', 'mppt_range_not_increasing', 'MPPT voltage range minimum should be below maximum.', $model, 'mppt_voltage_range');
                }
                if ($maxDc !== null && $mppt['max'] > $maxDc) {
                    $issues[] = $this->fieldIssue('warning', 'mppt_range_exceeds_max_dc', 'MPPT voltage range should not exceed maximum DC voltage.', $model, 'mppt_voltage_range');
                }
            }

            if ($inputCurrent !== null && $inputCurrent <= 0) {
                $issues[] = $this->fieldIssue('error', 'max_input_current_non_positive', 'Maximum input current must be positive.', $model, 'max_input_current');
            }

            if ($shortCircuit !== null && $inputCurrent !== null && $shortCircuit < $inputCurrent) {
                $issues[] = $this->fieldIssue('warning', 'short_circuit_current_below_input_current', 'Short-circuit current should be at least maximum input current.', $model, 'max_short_circuit_current');
            }

            if ($requiresMppt && ($mpptCount === null || $mpptCount <= 0)) {
                $issues[] = $this->fieldIssue('warning', 'mppt_count_missing_or_invalid', 'MPPT count should be positive for string/hybrid inverters.', $model, 'mppt_count');
            } elseif (! $isCentral && $mpptCount !== null && $mpptCount <= 0) {
                $issues[] = $this->fieldIssue('warning', 'mppt_count_non_positive', 'MPPT count should be positive when present.', $model, 'mppt_count');
            }

            if ($strings !== null && $strings <= 0) {
                $issues[] = $this->fieldIssue('warning', 'strings_per_mppt_non_positive', 'Strings per MPPT should be positive when present.', $model, 'strings_per_mppt');
            }
        }
    }

    /**
     * @param InverterValidationIssue[] $issues
     */
    private function validateAc(InverterDto $dto, array &$issues): void
    {
        $isCentral = $dto->deviceType === 'central_inverter';

        foreach ($dto->acOutput?->models ?? [] as $model) {
            if ($model->fields === []) {
                continue;
            }

            $ratedPower = $this->fieldNumber($model, 'rated_ac_power', prefer: 'max');
            $maxPower = $this->fieldNumber($model, 'max_ac_power', prefer: 'max');
            $ratedVa = $this->fieldNumber($model, 'rated_apparent_power', prefer: 'max');
            $maxVa = $this->fieldNumber($model, 'max_apparent_power', prefer: 'max');
            $ratedCurrent = $this->fieldNumber($model, 'rated_output_current', prefer: 'max');
            $maxCurrent = $this->fieldNumber($model, 'max_output_current', prefer: 'max');
            $voltage = $this->fieldNumber($model, 'rated_ac_voltage', prefer: 'max');

            if ($ratedPower !== null && $ratedPower <= 0) {
                $issues[] = $this->fieldIssue('error', 'rated_ac_power_non_positive', 'Rated AC power must be positive.', $model, 'rated_ac_power');
            }
            if (
                $maxPower !== null
                && $ratedPower !== null
                && $maxPower < $ratedPower * 0.98
                && ! $this->isOptionalCountrySpecificPowerNote($model->fields['max_ac_power'] ?? null)
            ) {
                $issues[] = $this->fieldIssue('warning', 'max_ac_power_below_rated', 'Maximum AC power should be at least rated AC power.', $model, 'max_ac_power');
            }
            if ($ratedVa !== null && $ratedPower !== null && $ratedVa < $ratedPower * 0.9) {
                $issues[] = $this->fieldIssue('warning', 'rated_apparent_power_below_rated_power', 'Rated apparent power should be close to or above rated AC power.', $model, 'rated_apparent_power');
            }
            if ($maxVa !== null && $ratedVa !== null && $maxVa < $ratedVa * 0.98) {
                $issues[] = $this->fieldIssue('warning', 'max_apparent_power_below_rated', 'Maximum apparent power should be at least rated apparent power.', $model, 'max_apparent_power');
            }
            if ($ratedCurrent !== null && $ratedCurrent <= 0) {
                $issues[] = $this->fieldIssue('error', 'rated_output_current_non_positive', 'Rated output current must be positive when present.', $model, 'rated_output_current');
            }
            if ($maxCurrent !== null && $maxCurrent <= 0) {
                $issues[] = $this->fieldIssue('error', 'max_output_current_non_positive', 'Maximum output current must be positive when present.', $model, 'max_output_current');
            }
            if ($voltage !== null && $voltage <= 0 && ! $this->isPercentOnly($model->fields['rated_ac_voltage'] ?? null)) {
                $issues[] = $this->fieldIssue('error', 'rated_ac_voltage_non_positive', 'Rated AC voltage must be positive when present.', $model, 'rated_ac_voltage');
            }

            $this->validateFrequency($model, $issues);
            $this->validateThd($model, $issues);
            $this->validatePowerFactor($model, $issues);

            if (! $isCentral && $this->isClearlyThreePhase($model) && $ratedPower !== null && $voltage !== null && ($ratedCurrent ?? $maxCurrent) !== null) {
                $current = $ratedCurrent ?? $maxCurrent;
                $expectedCurrent = ($ratedPower >= 1000 ? $ratedPower : $ratedPower * 1000) / (sqrt(3) * $voltage);
                if ($expectedCurrent > 0 && $current > 0) {
                    $ratio = $current / $expectedCurrent;
                    if ($ratio < 0.35 || $ratio > 2.8) {
                        $issues[] = $this->fieldIssue('warning', 'three_phase_power_current_mismatch', 'Rated current is not roughly consistent with rated power and voltage.', $model, $ratedCurrent !== null ? 'rated_output_current' : 'max_output_current');
                    }
                }
            }
        }
    }

    /**
     * @param InverterValidationIssue[] $issues
     */
    private function validateProtection(InverterDto $dto, array &$issues): void
    {
        $protectionCount = $this->protectionFieldCount($dto);

        if ($protectionCount === 0) {
            $issues[] = $this->issue('warning', 'missing_protection_block', 'No protection block was extracted for a supported inverter.');
            return;
        }

        $isStringLike = in_array($dto->deviceType, ['string_inverter', 'hybrid_inverter'], true);
        $isGridTied = in_array($dto->deviceType, ['string_inverter', 'hybrid_inverter', 'storage_inverter'], true);

        if ($isStringLike && $dto->protection?->hasDcSwitch === null && $dto->protection?->hasDcDisconnector === null) {
            $issues[] = $this->issue('warning', 'missing_dc_switch_or_disconnector', 'Neither DC switch nor DC disconnector was found for a string/hybrid inverter.');
        }

        if ($isGridTied && $dto->protection?->hasDcSpd === null && $dto->protection?->hasAcSpd === null) {
            $issues[] = $this->issue('warning', 'missing_spd_protection', 'Neither DC SPD nor AC SPD was found.');
        }

        if ($isGridTied && $dto->protection?->hasAntiIslandingProtection === null && $dto->protection?->hasGridMonitoring === null) {
            $issues[] = $this->issue('warning', 'missing_anti_islanding_or_grid_monitoring', 'Anti-islanding or grid monitoring was not found for a grid-tied inverter.');
        }
    }

    /**
     * @param InverterValidationIssue[] $issues
     */
    private function validateRatedPowerConditions(InverterDto $dto, array &$issues): void
    {
        foreach ($dto->ratedPowerConditions as $index => $condition) {
            if ($condition->powerKw === null || $condition->powerKw <= 0) {
                $issues[] = $this->issue('error', 'rated_power_condition_non_positive', 'Temperature-conditioned rated power must be positive.', field: 'rated_power_conditions', value: $condition->powerKw, context: ['index' => $index, 'source' => $condition->source?->toArray()]);
            }

            if ($condition->ambientTemperatureC !== null && ($condition->ambientTemperatureC < -40 || $condition->ambientTemperatureC > 80)) {
                $issues[] = $this->issue('warning', 'rated_power_condition_temperature_implausible', 'Ambient temperature on rated power condition is outside the plausible range.', field: 'rated_power_conditions', value: $condition->ambientTemperatureC, context: ['index' => $index, 'source' => $condition->source?->toArray()]);
            }
        }
    }

    /**
     * @param InverterValidationIssue[] $issues
     */
    private function validateFrequency(InverterElectricalModelDto $model, array &$issues): void
    {
        $field = $model->fields['rated_frequency'] ?? null;
        if ($field === null) {
            return;
        }

        $text = mb_strtolower((string) $field->value.' '.($field->sourceText ?? ''));
        $numbers = $this->numbers($field);
        $hasGridFrequency = count(array_filter($numbers, fn (float $number) => $number >= 45 && $number <= 65)) > 0;

        if (! $hasGridFrequency && ! preg_match('/(?:\b(?:50|60)\s*(?:\/|-)\s*(?:50|60)\s*h\s*z\b|\b(?:50|60)\s*h\s*z\b)/iu', $text)) {
            $issues[] = $this->fieldIssue('warning', 'frequency_unusual', 'Frequency should normally be 50 Hz, 60 Hz, or 50/60 Hz.', $model, 'rated_frequency');
        }
    }

    /**
     * @param InverterValidationIssue[] $issues
     */
    private function validateThd(InverterElectricalModelDto $model, array &$issues): void
    {
        $thd = isset($model->fields['thd']) ? $this->percentNumber($model->fields['thd']) : null;
        if ($thd !== null && $thd > 5) {
            $issues[] = $this->fieldIssue('warning', 'thd_high', 'THD is normally below 5%.', $model, 'thd');
        }
    }

    /**
     * @param InverterValidationIssue[] $issues
     */
    private function validatePowerFactor(InverterElectricalModelDto $model, array &$issues): void
    {
        $field = $model->fields['power_factor'] ?? null;
        if ($field === null) {
            return;
        }

        $text = mb_strtolower((string) $field->value.' '.($field->sourceText ?? ''));
        if (str_contains($text, 'leading') || str_contains($text, 'lagging') || str_contains($text, 'overexcited') || str_contains($text, 'underexcited')) {
            return;
        }

        $numbers = $this->numbers($field);
        if ($numbers === []) {
            return;
        }

        $pf = max(array_filter($numbers, fn (float $number) => $number <= 1.2) ?: $numbers);
        if ($pf < 0.8 || $pf > 1.05) {
            $issues[] = $this->fieldIssue('warning', 'power_factor_unusual', 'Power factor should normally be around 0.8 leading to 0.8 lagging, or equivalent notation.', $model, 'power_factor');
        }
    }

    /**
     * @param InverterElectricalModelDto[] $models
     * @param string[] $coreFields
     */
    private function blockScore(array $models, array $coreFields, int $maxScore): int
    {
        $models = array_filter($models, fn (InverterElectricalModelDto $model) => $model->fields !== []);
        if ($models === []) {
            return 0;
        }

        $fieldNames = [];
        foreach ($models as $model) {
            foreach (array_keys($model->fields) as $field) {
                $fieldNames[$field] = true;
            }
        }

        $corePresent = count(array_filter($coreFields, fn (string $field) => isset($fieldNames[$field])));
        $breadth = min(1.0, count($fieldNames) / 8);

        return (int) round($maxScore * (($corePresent / max(1, count($coreFields))) * 0.65 + $breadth * 0.35));
    }

    private function protectionFieldCount(InverterDto $dto): int
    {
        return count(array_filter(
            $dto->protection?->toArray() ?? [],
            fn ($value, string $key) => $key !== 'metadata' && $value !== null,
            ARRAY_FILTER_USE_BOTH,
        ));
    }

    /**
     * @param InverterElectricalModelDto[] $models
     */
    private function allModelsEmpty(array $models): bool
    {
        if ($models === []) {
            return true;
        }

        foreach ($models as $model) {
            if ($model->fields !== []) {
                return false;
            }
        }

        return true;
    }

    private function fieldNumber(InverterElectricalModelDto $model, string $field, string $prefer = 'first'): ?float
    {
        $numbers = isset($model->fields[$field]) ? $this->numbers($model->fields[$field]) : [];
        if ($numbers === []) {
            return null;
        }

        return match ($prefer) {
            'min' => min($numbers),
            'max' => max($numbers),
            default => $numbers[0],
        };
    }

    private function percentNumber(InverterSourceValueDto $value): ?float
    {
        $text = (string) $value->value.' '.($value->sourceText ?? '');

        if (preg_match('/[-+]?\d+(?:[.,]\d+)?\s*%/u', $text, $match) !== 1) {
            return null;
        }

        return $this->parseNumber(preg_replace('/[^\d.,+-]/u', '', $match[0]) ?? $match[0]);
    }

    /**
     * @return array{min:float,max:float}|null
     */
    private function fieldRange(InverterElectricalModelDto $model, string $field): ?array
    {
        if (! isset($model->fields[$field])) {
            return null;
        }

        $numbers = $this->numbers($model->fields[$field]);
        $numbers = array_values(array_filter($numbers, fn (float $number) => $number > 10));

        if (count($numbers) < 2) {
            return null;
        }

        return ['min' => min($numbers), 'max' => max($numbers)];
    }

    /**
     * @return float[]
     */
    private function numbers(InverterSourceValueDto $value): array
    {
        $text = trim((string) $value->value);
        if ($text === '') {
            $text = trim((string) ($value->sourceText ?? ''));
        }

        preg_match_all('/[-+]?\d+(?:[.,]\d+)?/u', $text, $matches);
        $numbers = [];

        foreach ($matches[0] ?? [] as $raw) {
            $numbers[] = $this->parseNumber($raw);
        }

        while (count($numbers) > 1 && $numbers[0] > 0 && $numbers[0] < 10 && max($numbers) >= 100) {
            array_shift($numbers);
        }

        return $numbers;
    }

    private function isOptionalCountrySpecificPowerNote(?InverterSourceValueDto $value): bool
    {
        if ($value === null) {
            return false;
        }

        $text = mb_strtolower((string) $value->value.' '.($value->sourceText ?? ''));

        return str_contains($text, 'for germany')
            || str_contains($text, 'country')
            || str_contains($text, 'optional');
    }

    private function isPercentOnly(?InverterSourceValueDto $value): bool
    {
        if ($value === null) {
            return false;
        }

        $text = trim((string) $value->value);

        return $text !== '' && str_contains($text, '%') && ! preg_match('/\b(?:v|vac|kv)\b/iu', $text);
    }

    private function isClearlyThreePhase(InverterElectricalModelDto $model): bool
    {
        $field = $model->fields['phase_type'] ?? $model->fields['rated_ac_voltage'] ?? null;

        if ($field === null) {
            return false;
        }

        $text = mb_strtolower((string) $field->value.' '.($field->sourceText ?? ''));

        return str_contains($text, 'three')
            || str_contains($text, '3/n')
            || str_contains($text, '3 / n')
            || str_contains($text, '3p')
            || str_contains($text, '3 / 3')
            || str_contains($text, '3-pe');
    }

    private function parseNumber(string $raw): float
    {
        $number = trim($raw);

        if (preg_match('/^\d{1,3}(?:,\d{3})+(?:\.\d+)?$/u', $number) === 1) {
            return (float) str_replace(',', '', $number);
        }

        if (preg_match('/^\d+,\d{1,2}$/u', $number) === 1) {
            return (float) str_replace(',', '.', $number);
        }

        return (float) str_replace(',', '', $number);
    }

    private function fieldIssue(string $severity, string $code, string $message, InverterElectricalModelDto $model, string $field): InverterValidationIssue
    {
        $source = $model->fields[$field] ?? null;

        return $this->issue(
            severity: $severity,
            code: $code,
            message: $message,
            modelName: $model->model,
            field: $field,
            value: $source?->value,
            context: $source?->toArray(),
        );
    }

    private function issue(string $severity, string $code, string $message, ?string $modelName = null, ?string $field = null, string|float|int|bool|array|null $value = null, ?array $context = null): InverterValidationIssue
    {
        return new InverterValidationIssue(
            severity: $severity,
            code: $code,
            message: $message,
            modelName: $modelName,
            field: $field,
            value: $value,
            context: $context,
        );
    }

    private function grade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 75 => 'B',
            $score >= 60 => 'C',
            $score >= 40 => 'D',
            default => 'F',
        };
    }
}
