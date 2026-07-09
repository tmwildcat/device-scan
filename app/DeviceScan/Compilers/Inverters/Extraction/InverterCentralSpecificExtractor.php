<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\Extraction;

use App\DeviceScan\Compilers\Inverters\DTO\InverterCentralSpecificDto;
use App\DeviceScan\Compilers\Inverters\DTO\InverterSourceValueDto;
use App\DeviceScan\Compilers\Inverters\InverterSectionDetector;
use App\DeviceScan\Compilers\Inverters\InverterTextDocument;

final class InverterCentralSpecificExtractor
{
    public function extract(InverterTextDocument $document): InverterCentralSpecificDto
    {
        $fields = [];

        foreach ($document->pages as $page => $text) {
            foreach (preg_split('/\R/u', $text) ?: [] as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                $this->extractFromLine($fields, $line, (int) $page);
            }
        }

        return new InverterCentralSpecificDto(
            maxDcInputs: $fields['max_dc_inputs'] ?? null,
            dcCabinetInputs: $fields['dc_cabinet_inputs'] ?? null,
            dcCombinerRequired: $fields['dc_combiner_required'] ?? null,
            mvStationInterface: $fields['mv_station_interface'] ?? null,
            transformerInterface: $fields['transformer_interface'] ?? null,
            gridVoltageMv: $fields['grid_voltage_mv'] ?? null,
            acBreaker: $fields['ac_breaker'] ?? null,
            coolingSystem: $fields['cooling_system'] ?? null,
            containerized: $fields['containerized'] ?? null,
            mpptCount: $fields['mppt_count'] ?? null,
            inverterBlocks: $fields['inverter_blocks'] ?? null,
            metadata: ['method' => 'central_inverter_text_terms'],
        );
    }

    /**
     * @param array<string,InverterSourceValueDto> $fields
     */
    private function extractFromLine(array &$fields, string $line, int $page): void
    {
        $lower = mb_strtolower(preg_replace('/\s+/u', ' ', $line) ?? $line);
        $compact = mb_strtolower(preg_replace('/[^a-z0-9]+/iu', '', $line) ?? $line);

        if ($this->matchesAny($compact, ['noofdcinputs', 'numberofprotecteddcinputs', 'standardnumberofinputs'])) {
            $this->put($fields, 'max_dc_inputs', $this->firstNumberOrText($line), null, $line, $page, 0.8);
        }

        if (str_contains($compact, 'dccabinet') || str_contains($lower, 'integrated dc cabinet')) {
            $this->put($fields, 'dc_cabinet_inputs', true, null, $line, $page, 0.72);
        }

        if (str_contains($lower, 'junction boxes') || str_contains($compact, 'combiner')) {
            $this->put($fields, 'dc_combiner_required', $this->negativeIfConnectedDirectly($line), null, $line, $page, 0.62);
        }

        if ($this->matchesAny($compact, ['mvpowerstation', 'mvswitchgear', 'mvgridconnection', 'mediumvoltagestation'])) {
            $this->put($fields, 'mv_station_interface', $line, null, $line, $page, 0.76);
        }

        if ($this->matchesAny($compact, ['transformervector', 'transformercoolingtype', 'mvtransformer', 'includingtransformer'])) {
            $this->put($fields, 'transformer_interface', $line, null, $line, $page, 0.78);
        }

        if ($this->matchesAny($compact, ['nominalacvoltage', 'lvmvvoltage', 'gridvoltage']) && preg_match('/\b\d+(?:[.,]\d+)?\s*kV\b/iu', $line) === 1) {
            $this->put($fields, 'grid_voltage_mv', $line, 'kV', $line, $page, 0.78);
        }

        if ($this->matchesAny($compact, ['acbreaker', 'acprotection', 'acoutputprotection', 'acsideDisconnection'])) {
            $this->put($fields, 'ac_breaker', $line, null, $line, $page, 0.76);
        }

        if ($this->matchesAny($compact, ['coolingsystem', 'coolingmethod', 'coolingmode', 'transformercoolingtype', 'advancedhybridcooling'])) {
            $this->put($fields, 'cooling_system', $line, null, $line, $page, 0.8);
        }

        if ($this->matchesAny($compact, ['containerized', 'containersize', 'containerizedsolution', '20footcontainer'])) {
            $this->put($fields, 'containerized', true, null, $line, $page, 0.76);
        }

        if ($this->matchesAny($compact, ['numberofmppt', 'numberofmppttrackers', 'noofindependentmppinputs'])) {
            $this->put($fields, 'mppt_count', $this->firstNumberOrText($line), null, $line, $page, 0.82);
        }

        if ($this->matchesAny($compact, ['numberofpowermodules', 'invertercircuit1', 'invertercircuit2', 'powerstack'])) {
            $this->put($fields, 'inverter_blocks', $this->firstNumberOrText($line), null, $line, $page, 0.72);
        }
    }

    /**
     * @param array<string,InverterSourceValueDto> $fields
     */
    private function put(array &$fields, string $field, string|float|int|bool $value, ?string $unit, string $line, int $page, float $confidence): void
    {
        if (isset($fields[$field]) && ($fields[$field]->confidence ?? 0.0) >= $confidence) {
            return;
        }

        $fields[$field] = new InverterSourceValueDto(
            value: $value,
            unit: $unit,
            sourceText: $line,
            sourcePage: $page,
            sourceSection: InverterSectionDetector::CENTRAL_SPECIFIC,
            confidence: $confidence,
            metadata: [
                'method' => 'central_specific_term_match',
                'field' => $field,
            ],
            normalizedValue: is_string($value) ? $this->normalized($value) : $value,
        );
    }

    private function firstNumberOrText(string $line): string|float|int
    {
        if (preg_match('/\b\d+(?:[.,]\d+)?\b/u', $line, $match) === 1) {
            $number = (float) str_replace(',', '.', $match[0]);

            return floor($number) === $number ? (int) $number : $number;
        }

        return $line;
    }

    private function negativeIfConnectedDirectly(string $line): bool|string
    {
        return str_contains(mb_strtolower($line), 'connected directly') ? false : $line;
    }

    private function normalized(string $value): float|string
    {
        if (preg_match('/-?\d+(?:[.,]\d+)?/u', $value, $match) === 1) {
            return (float) str_replace(',', '.', $match[0]);
        }

        return $value;
    }

    /**
     * @param string[] $needles
     */
    private function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, mb_strtolower($needle))) {
                return true;
            }
        }

        return false;
    }
}
