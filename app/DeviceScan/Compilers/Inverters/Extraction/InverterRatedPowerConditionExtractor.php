<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\Extraction;

use App\DeviceScan\Compilers\Inverters\DTO\InverterRatedPowerConditionDto;
use App\DeviceScan\Compilers\Inverters\DTO\InverterSourceValueDto;
use App\DeviceScan\Compilers\Inverters\InverterSectionDetector;
use App\DeviceScan\Compilers\Inverters\InverterTextDocument;

final class InverterRatedPowerConditionExtractor
{
    /**
     * @return InverterRatedPowerConditionDto[]
     */
    public function extract(InverterTextDocument $document): array
    {
        $conditions = [];

        foreach ($document->pages as $page => $text) {
            foreach (preg_split('/\R/u', $text) ?: [] as $line) {
                $line = trim($line);

                if ($line === '' || ! $this->isPowerRatingLine($line)) {
                    continue;
                }

                foreach ($this->conditionsFromLine($line, (int) $page) as $condition) {
                    $conditions[] = $condition;
                }
            }
        }

        return $this->unique($conditions);
    }

    private function isPowerRatingLine(string $line): bool
    {
        $compact = mb_strtolower(preg_replace('/[^a-z0-9@℃°]+/iu', '', $line) ?? $line);

        return str_contains($compact, 'ratedpower')
            || str_contains($compact, 'acoutputpower')
            || str_contains($compact, 'nominalacpower')
            || str_contains($compact, 'nominalpower')
            || str_contains($compact, 'maximumpower')
            || str_contains($compact, 'ratedoutputpower');
    }

    /**
     * @return InverterRatedPowerConditionDto[]
     */
    private function conditionsFromLine(string $line, int $page): array
    {
        $conditions = [];

        preg_match_all('/(?P<power>\d+(?:[.,]\d+)?)\s*(?P<unit>MW|MVA|kW|kVA)\s*@\s*(?P<temp>-?\d+(?:[.,]\d+)?)\s*(?:℃|°\s*C|ºC|C)\b/iu', $line, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $conditions[] = $this->condition(
                $this->toKw((float) str_replace(',', '.', $match['power']), $match['unit']),
                (float) str_replace(',', '.', $match['temp']),
                'continuous',
                $line,
                $page,
                $match['unit'],
            );
        }

        if ($conditions !== []) {
            return $conditions;
        }

        if (preg_match('/@/u', $line) === 1) {
            return [];
        }

        preg_match_all('/(?P<power>\d+(?:[.,]\d+)?)\s*(?P<unit>MW|MVA|kW|kVA)\b/iu', $line, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $conditions[] = $this->condition(
                $this->toKw((float) str_replace(',', '.', $match['power']), $match['unit']),
                null,
                'continuous',
                $line,
                $page,
                $match['unit'],
            );
        }

        return $conditions;
    }

    private function condition(float $powerKw, ?float $temperatureC, string $condition, string $line, int $page, string $sourceUnit): InverterRatedPowerConditionDto
    {
        return new InverterRatedPowerConditionDto(
            powerKw: $powerKw,
            ambientTemperatureC: $temperatureC,
            condition: $condition,
            source: new InverterSourceValueDto(
                value: $powerKw,
                unit: 'kW',
                sourceText: $line,
                sourcePage: $page,
                sourceSection: InverterSectionDetector::AC_OUTPUT,
                confidence: $temperatureC === null ? 0.62 : 0.82,
                metadata: [
                    'method' => 'rated_power_condition_text',
                    'source_unit' => $sourceUnit,
                ],
                normalizedValue: $powerKw,
            ),
            metadata: [
                'source_unit' => $sourceUnit,
            ],
        );
    }

    private function toKw(float $value, string $unit): float
    {
        return match (mb_strtolower($unit)) {
            'mw', 'mva' => $value * 1000,
            default => $value,
        };
    }

    /**
     * @param InverterRatedPowerConditionDto[] $conditions
     * @return InverterRatedPowerConditionDto[]
     */
    private function unique(array $conditions): array
    {
        $seen = [];
        $result = [];

        foreach ($conditions as $condition) {
            $key = ($condition->powerKw ?? '').'|'.($condition->ambientTemperatureC ?? '').'|'.$condition->source?->sourceText;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $condition;
        }

        return $result;
    }
}
