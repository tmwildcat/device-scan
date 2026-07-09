<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\Extraction;

use App\DeviceScan\Compilers\Inverters\DTO\InverterProtectionDto;
use App\DeviceScan\Compilers\Inverters\DTO\InverterSourceValueDto;
use App\DeviceScan\Compilers\Inverters\InverterSectionDetector;
use App\DeviceScan\Compilers\Inverters\InverterTextDocument;

final class InverterProtectionExtractor
{
    public function extract(InverterTextDocument $document): InverterProtectionDto
    {
        $fields = [];

        foreach ($document->pages as $page => $text) {
            $lines = preg_split('/\R/u', $text) ?: [];

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                $this->extractFromLine($fields, $line, (int) $page);
            }
        }

        return new InverterProtectionDto(
            hasDcSwitch: $fields['has_dc_switch'] ?? null,
            hasDcDisconnector: $fields['has_dc_disconnector'] ?? null,
            hasDcReversePolarityProtection: $fields['has_dc_reverse_polarity_protection'] ?? null,
            hasDcSpd: $fields['has_dc_spd'] ?? null,
            dcSpdType: $fields['dc_spd_type'] ?? null,
            hasAcSpd: $fields['has_ac_spd'] ?? null,
            acSpdType: $fields['ac_spd_type'] ?? null,
            hasAcShortCircuitProtection: $fields['has_ac_short_circuit_protection'] ?? null,
            hasAcOvercurrentProtection: $fields['has_ac_overcurrent_protection'] ?? null,
            hasAntiIslandingProtection: $fields['has_anti_islanding_protection'] ?? null,
            hasGroundFaultMonitoring: $fields['has_ground_fault_monitoring'] ?? null,
            hasInsulationMonitoring: $fields['has_insulation_monitoring'] ?? null,
            hasResidualCurrentMonitoring: $fields['has_residual_current_monitoring'] ?? null,
            hasRcmu: $fields['has_rcmu'] ?? null,
            hasAfci: $fields['has_afci'] ?? null,
            hasPidRecovery: $fields['has_pid_recovery'] ?? null,
            hasStringCurrentMonitoring: $fields['has_string_current_monitoring'] ?? null,
            hasGridMonitoring: $fields['has_grid_monitoring'] ?? null,
            metadata: ['method' => 'poppler_layout_text_protection_terms'],
        );
    }

    /**
     * @param array<string,InverterSourceValueDto> $fields
     */
    private function extractFromLine(array &$fields, string $line, int $page): void
    {
        $lower = mb_strtolower(preg_replace('/\s+/u', ' ', $line) ?? $line);
        $compact = mb_strtolower(preg_replace('/[^a-z0-9+\/]+/iu', '', $line) ?? $line);

        if ($this->matchesAny($compact, ['dcswitch'])) {
            $this->putBool($fields, 'has_dc_switch', $line, $page, 0.86);
        }

        if ($this->matchesAny($compact, ['dcdisconnector', 'inputsidedisconnectiondevice', 'dcisolationmeasurement'])) {
            $this->putBool($fields, 'has_dc_disconnector', $line, $page, 0.84);
        }

        if ($this->matchesAny($compact, ['dcreversepolarityprotection', 'dcreversepolarity', 'dcreverseconnectionprotection', 'reversepolarityprotection'])) {
            $this->putBool($fields, 'has_dc_reverse_polarity_protection', $line, $page, 0.88);
        }

        if ($this->matchesAny($compact, ['acshortcircuitprotection', 'acshortcircuitcurrentcapability', 'acshortcircuit'])) {
            $this->putBool($fields, 'has_ac_short_circuit_protection', $line, $page, 0.86);
        }

        if ($this->matchesAny($compact, ['acovercurrentprotection', 'outputovercurrentprotection', 'maxacinputovercurrentprotection'])) {
            $this->putBool($fields, 'has_ac_overcurrent_protection', $line, $page, 0.82);
        }

        if ($this->matchesAny($compact, ['antiislandingprotection', 'antiislanding'])) {
            $this->putBool($fields, 'has_anti_islanding_protection', $line, $page, 0.88);
        }

        if ($this->matchesAny($compact, ['groundfaultmonitoring'])) {
            $this->putBool($fields, 'has_ground_fault_monitoring', $line, $page, 0.88);
        }

        if ($this->matchesAny($compact, ['insulationresistancemonitoring', 'insulationmonitoring'])) {
            $this->putBool($fields, 'has_insulation_monitoring', $line, $page, 0.88);
        }

        if ($this->matchesAny($compact, ['residualcurrentmonitoringunit', 'residualcurrentmonitoring', 'leakagecurrentprotection'])) {
            $this->putBool($fields, 'has_residual_current_monitoring', $line, $page, 0.84);
        }

        if ($this->matchesAny($compact, ['rcmu'])) {
            $this->putBool($fields, 'has_rcmu', $line, $page, 0.9);
            $this->putBool($fields, 'has_residual_current_monitoring', $line, $page, 0.82);
        }

        if ($this->matchesAny($compact, ['afci', 'arcfaultcircuitinterrupter', 'arcfaultprotection'])) {
            $this->putBool($fields, 'has_afci', $line, $page, 0.88);
        }

        if ($this->matchesAny($compact, ['pidrecoveryfunction', 'pidrecovery'])) {
            $this->putBool($fields, 'has_pid_recovery', $line, $page, 0.88);
        }

        if ($this->matchesAny($compact, ['pvstringcurrentmonitoring', 'stringcurrentmonitoring'])) {
            $this->putBool($fields, 'has_string_current_monitoring', $line, $page, 0.88);
        }

        if ($this->matchesAny($compact, ['gridmonitoring'])) {
            $this->putBool($fields, 'has_grid_monitoring', $line, $page, 0.88);
        }

        $this->extractSurgeProtection($fields, $line, $lower, $compact, $page);
    }

    /**
     * @param array<string,InverterSourceValueDto> $fields
     */
    private function extractSurgeProtection(array &$fields, string $line, string $lower, string $compact, int $page): void
    {
        if (! $this->matchesAny($compact, ['spd', 'surgeprotection', 'surgearrester'])) {
            return;
        }

        $mentionsDc = preg_match('/\bd\s*c\b/iu', $line) === 1
            || $this->matchesAny($compact, ['dcsurge', 'dcspd', 'dctype']);
        $mentionsAc = preg_match('/\ba\s*c\b/iu', $line) === 1
            || $this->matchesAny($compact, ['acsurge', 'acspd', 'actype']);

        if (str_contains($compact, 'ac/dcsurgeprotection') || str_contains($compact, 'dc&acspd') || str_contains($compact, 'dcacsurgeprotection')) {
            $mentionsDc = true;
            $mentionsAc = true;
        }

        if ($mentionsDc) {
            $this->putBool($fields, 'has_dc_spd', $line, $page, 0.86);
        }

        if ($mentionsAc) {
            $this->putBool($fields, 'has_ac_spd', $line, $page, 0.86);
        }

        if (! $mentionsDc && ! $mentionsAc && str_contains($compact, 'surgeprotection')) {
            $this->putBool($fields, 'has_dc_spd', $line, $page, 0.55);
            $this->putBool($fields, 'has_ac_spd', $line, $page, 0.55);
        }

        $this->extractExplicitSideTypes($fields, $line, $lower, $compact, $page);
    }

    /**
     * @param array<string,InverterSourceValueDto> $fields
     */
    private function extractExplicitSideTypes(array &$fields, string $line, string $lower, string $compact, int $page): void
    {
        if (preg_match('/dc\s+type\s*([iIvVxX0-9+\-\/]+)/iu', $line, $dcMatch) === 1) {
            $this->putType($fields, 'dc_spd_type', $this->normalizeType($dcMatch[1]), $line, $page, 0.9);
        }

        if (preg_match('/ac\s+type\s*([iIvVxX0-9+\-\/]+)/iu', $line, $acMatch) === 1) {
            $this->putType($fields, 'ac_spd_type', $this->normalizeType($acMatch[1]), $line, $page, 0.9);
        }

        if (preg_match('/type\s*([iIvVxX0-9+\-\/]+)\s*(?:dc\s*&\s*ac|dc\s+and\s+ac)/iu', $line, $bothMatch) === 1) {
            $type = $this->normalizeType($bothMatch[1]);
            $this->putType($fields, 'dc_spd_type', $type, $line, $page, 0.88);
            $this->putType($fields, 'ac_spd_type', $type, $line, $page, 0.88);
        }

        if (preg_match('/ac\s*\/\s*dc\s+type\s*([iIvVxX0-9+\-\/]+)\s*\/\s*type\s*([iIvVxX0-9+\-\/]+)/iu', $line, $splitMatch) === 1) {
            $this->putType($fields, 'ac_spd_type', $this->normalizeType($splitMatch[1]), $line, $page, 0.88);
            $this->putType($fields, 'dc_spd_type', $this->normalizeType($splitMatch[2]), $line, $page, 0.88);
        }

        if (preg_match('/ac\/dc\s+surge\s+protection\s+type\s*([iIvVxX0-9+\-\/]+)\s*\/\s*type\s*([iIvVxX0-9+\-\/]+)/iu', $line, $splitMatch) === 1) {
            $this->putType($fields, 'dc_spd_type', $this->normalizeType($splitMatch[1]), $line, $page, 0.84);
            $this->putType($fields, 'ac_spd_type', $this->normalizeType($splitMatch[2]), $line, $page, 0.84);
        }

        if (str_contains($compact, 'dctypeii/ac typeii') || str_contains($compact, 'dctypeii/actypeii')) {
            $this->putType($fields, 'dc_spd_type', 'Type II', $line, $page, 0.9);
            $this->putType($fields, 'ac_spd_type', 'Type II', $line, $page, 0.9);
        }

        if (str_contains($compact, 'typeiidc&acspd') || str_contains($compact, 'typeiidcandacspd')) {
            $this->putType($fields, 'dc_spd_type', 'Type II', $line, $page, 0.9);
            $this->putType($fields, 'ac_spd_type', 'Type II', $line, $page, 0.9);
        }

        if (str_contains($compact, 'dcsurgeprotectiontype1+2')) {
            $this->putType($fields, 'dc_spd_type', 'Type 1+2', $line, $page, 0.9);
        }

        if (str_contains($compact, 'acsurgeprotectiontype1+2')) {
            $this->putType($fields, 'ac_spd_type', 'Type 1+2', $line, $page, 0.9);
        }

        if (str_contains($compact, 'dcsurgeprotectiontype2') || str_contains($compact, 'dcsurgeprotectiontypeii')) {
            $this->putType($fields, 'dc_spd_type', 'Type II', $line, $page, 0.9);
        }

        if (str_contains($compact, 'acsurgeprotectiontype2') || str_contains($compact, 'acsurgeprotectiontypeii')) {
            $this->putType($fields, 'ac_spd_type', 'Type II', $line, $page, 0.9);
        }

        if (str_contains($compact, 'dcsurgeprotection') && str_contains($compact, 'typeii')) {
            $this->putType($fields, 'dc_spd_type', 'Type II', $line, $page, 0.88);
        }

        if (str_contains($compact, 'acsurgeprotection') && str_contains($compact, 'typeii')) {
            $this->putType($fields, 'ac_spd_type', 'Type II', $line, $page, 0.88);
        }

        if (str_contains($compact, 'dcsurgeprotectiontypeiii')) {
            $this->putType($fields, 'dc_spd_type', 'Type III', $line, $page, 0.82);
        }

        if (str_contains($compact, 'acsurgeprotectiontypeiii')) {
            $this->putType($fields, 'ac_spd_type', 'Type III', $line, $page, 0.82);
        }

        if (str_contains($compact, 'dcsurgeprotectiontypeii')) {
            $this->putType($fields, 'dc_spd_type', 'Type II', $line, $page, 0.9);
        }

        if (str_contains($compact, 'typespdondcside') || str_contains($compact, 'typeiispdondcside')) {
            $this->putType($fields, 'dc_spd_type', 'Type II', $line, $page, 0.84);
        }

        if (str_contains($compact, 'dcspdtype1+2') || str_contains($compact, 'dcspdtype1/2')) {
            $this->putType($fields, 'dc_spd_type', 'Type 1+2', $line, $page, 0.86);
        }

        if (str_contains($compact, 'acspdtype1+2') || str_contains($compact, 'acspdtype1/2')) {
            $this->putType($fields, 'ac_spd_type', 'Type 1+2', $line, $page, 0.86);
        }

        if ((str_contains($compact, 'dcsurgeprotection') || str_contains($compact, 'dcspd')) && preg_match('/type\s*([iIvVxX0-9+\-\/]+)/iu', $line, $typeMatch) === 1) {
            $this->putType($fields, 'dc_spd_type', $this->normalizeType($typeMatch[1]), $line, $page, 0.72);
        }

        if ((str_contains($compact, 'acsurgeprotection') || str_contains($compact, 'acspd')) && preg_match('/type\s*([iIvVxX0-9+\-\/]+)/iu', $line, $typeMatch) === 1) {
            $this->putType($fields, 'ac_spd_type', $this->normalizeType($typeMatch[1]), $line, $page, 0.72);
        }

        if (str_contains($lower, 'type ii') && str_contains($compact, 'dc') && str_contains($compact, 'ac')) {
            $this->putType($fields, 'dc_spd_type', 'Type II', $line, $page, 0.8);
            $this->putType($fields, 'ac_spd_type', 'Type II', $line, $page, 0.8);
        }
    }

    /**
     * @param array<string,InverterSourceValueDto> $fields
     */
    private function putBool(array &$fields, string $field, string $line, int $page, float $confidence): void
    {
        if (isset($fields[$field]) && ($fields[$field]->confidence ?? 0.0) >= $confidence) {
            return;
        }

        $fields[$field] = $this->sourceValue(true, $line, $page, $field, $confidence, true);
    }

    /**
     * @param array<string,InverterSourceValueDto> $fields
     */
    private function putType(array &$fields, string $field, string $type, string $line, int $page, float $confidence): void
    {
        if ($type === '') {
            return;
        }

        if (isset($fields[$field]) && ($fields[$field]->confidence ?? 0.0) >= $confidence) {
            return;
        }

        $fields[$field] = $this->sourceValue($type, $line, $page, $field, $confidence, $type);
    }

    private function sourceValue(string|bool $value, string $line, int $page, string $field, float $confidence, string|bool $normalized): InverterSourceValueDto
    {
        return new InverterSourceValueDto(
            value: $value,
            sourceText: $line,
            sourcePage: $page,
            sourceSection: InverterSectionDetector::PROTECTION,
            confidence: $confidence,
            metadata: [
                'method' => 'protection_term_match',
                'field' => $field,
            ],
            normalizedValue: $normalized,
        );
    }

    /**
     * @param string[] $needles
     */
    private function matchesAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeType(string $raw): string
    {
        $raw = trim($raw, " \t\n\r\0\x0B,.;:*)(");
        $raw = str_replace(['Ⅱ', 'Ⅲ', 'Ⅰ'], ['II', 'III', 'I'], $raw);
        $raw = preg_replace('/\s+/u', ' ', $raw) ?? $raw;
        $upper = mb_strtoupper($raw);

        return match ($upper) {
            '1', 'I' => 'Type I',
            '2', 'II' => 'Type II',
            '3', 'III' => 'Type III',
            '1+2', '1/2', 'I+II', 'I/II' => 'Type 1+2',
            default => str_starts_with($upper, 'TYPE') ? 'Type '.trim(substr($raw, 4)) : 'Type '.$raw,
        };
    }
}
