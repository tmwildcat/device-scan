<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Engineering\Module\Electrical;

use App\DeviceScan\Processing\Tables\Engineering\Common\EngineeringTable;
use App\DeviceScan\Processing\Tables\Engineering\Common\EngineeringTableRow;

final class ModuleElectricalCharacteristicsParser
{
    public function parse(EngineeringTable $table): ?CanonicalModuleElectricalCharacteristics
    {
        if ($table->type !== 'electrical') {
            return null;
        }

        if (count($table->models) < 2) {
            return null;
        }

        $variants = [];

        foreach ($table->models as $model) {
            $variants[] = new CanonicalModuleElectricalVariant(
                model: (string) $model,
                ratedMaxPowerW: $this->valueFor($table, 'rated_max_power', (string) $model),
                openCircuitVoltageV: $this->valueFor($table, 'open_circuit_voltage', (string) $model),
                maximumPowerVoltageV: $this->valueFor($table, 'maximum_power_voltage', (string) $model),
                shortCircuitCurrentA: $this->valueFor($table, 'short_circuit_current', (string) $model),
                maximumPowerCurrentA: $this->valueFor($table, 'maximum_power_current', (string) $model),
                moduleEfficiencyPercent: $this->valueFor($table, 'module_efficiency', (string) $model),
                metadata: [
                    'source' => self::class,
                ],
            );
        }

        return new CanonicalModuleElectricalCharacteristics(
            variants: $variants,
            metadata: [
                'source' => self::class,
                'table_type' => $table->type,
                'model_count' => count($table->models),
            ],
        );
    }

    private function valueFor(EngineeringTable $table, string $parameter, string $model): ?float
    {
        $row = $this->rowFor($table, $parameter);

        if ($row === null) {
            return null;
        }

        $value = $row->values[$model] ?? null;

        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        return $this->toFloat((string) $value);
    }

    private function rowFor(EngineeringTable $table, string $parameter): ?EngineeringTableRow
    {
        foreach ($table->rows as $row) {
            if ($row->parameter === $parameter) {
                return $row;
            }
        }

        return null;
    }

    private function toFloat(string $value): ?float
    {
        if (! preg_match('/-?\d+(?:[.,]\d+)?/u', $value, $match)) {
            return null;
        }

        return (float) str_replace(',', '.', $match[0]);
    }
}