<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters;

use App\DeviceScan\Compilers\Inverters\DTO\InverterDetectedSectionDto;

final class InverterSectionDetector
{
    public const DC_INPUT = 'INVERTER_DC_INPUT';
    public const AC_OUTPUT = 'INVERTER_AC_OUTPUT';
    public const PROTECTION = 'INVERTER_PROTECTION';
    public const CENTRAL_SPECIFIC = 'INVERTER_CENTRAL_SPECIFIC';
    public const GENERAL = 'INVERTER_GENERAL';

    /**
     * @return InverterDetectedSectionDto[]
     */
    public function detect(InverterTextDocument $document): array
    {
        $sections = [];

        foreach ($document->pages as $page => $text) {
            $lines = preg_split('/\R/u', $text) ?: [];

            foreach ($lines as $index => $line) {
                $type = $this->sectionType($line);

                if ($type === null) {
                    continue;
                }

                $sections[] = new InverterDetectedSectionDto(
                    type: $type,
                    title: trim($line),
                    page: (int) $page,
                    startLine: $index,
                    endLine: min(count($lines) - 1, $index + 24),
                    metadata: ['detector' => self::class],
                );
            }
        }

        return $sections;
    }

    private function sectionType(string $line): ?string
    {
        $line = mb_strtolower(trim($line));
        $compact = preg_replace('/[^a-z0-9&]+/iu', '', $line) ?? $line;

        if ($line === '') {
            return null;
        }

        if (preg_match('/^(input(?:\s+\(dc\)|\s+data\s+\(dc\))?|dc input|input data \(dc\))/u', $line) === 1) {
            return self::DC_INPUT;
        }

        if (preg_match('/^(output(?:\s+\(ac\)|\s+data\s+\(ac\))?|ac output|output data \(ac\))/u', $line) === 1) {
            return self::AC_OUTPUT;
        }

        if (
            preg_match('/^(protection(?:\s*&\s*function|s?\s*&\s*functions)?|protective devices|protection devices|features\s*&\s*protections)/u', $line) === 1
            || str_contains($line, 'protection&function')
            || str_contains($compact, 'features&protections')
        ) {
            return self::PROTECTION;
        }

        if (str_contains($line, 'technical specification') || str_contains($line, 'technical data')) {
            return self::GENERAL;
        }

        return null;
    }
}
