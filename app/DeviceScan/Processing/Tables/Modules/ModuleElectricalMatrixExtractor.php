<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Modules;

use App\DeviceScan\Processing\Document\Page;
use App\DeviceScan\Processing\Tables\DetectedTable;
use App\DeviceScan\Processing\Tables\DetectedTableCell;
use App\DeviceScan\Processing\Tables\DetectedTableRow;

final class ModuleElectricalMatrixExtractor
{
    public function extract(Page $page): ?DetectedTable
    {
        $text = $page->text?->content;

        if (! is_string($text) || trim($text) === '') {
            return null;
        }

        if (! str_contains(mb_strtolower($text), 'specifications')) {
            return null;
        }

        $rows = [];

        foreach ($this->rowDefinitions() as $definition) {
            $row = $this->extractRow($text, $definition);

            if ($row !== null) {
                $rows[] = $row;
            }
        }

        if (count($rows) < 3) {
            return null;
        }

        return new DetectedTable(
            title: 'Specifications',
            page: $page->number,
            models: $this->extractModels($text),
            rows: $rows,
            metadata: [
                'source' => self::class,
                'type' => 'module_electrical_matrix',
            ],
        );
    }

    private function extractRow(string $text, array $definition): ?DetectedTableRow
    {
        if (! preg_match($definition['pattern'], $text, $match)) {
            return null;
        }

        $raw = trim($match[0]);
        $values = $this->extractValues($raw);

        return new DetectedTableRow(
            label: $definition['label'],
            cells: array_map(
                fn (array $value) => new DetectedTableCell(
                    value: $value['value'],
                    displayValue: $value['raw'],
                    unit: $value['unit'],
                ),
                $values,
            ),
        );
    }

    private function extractModels(string $text): array
    {
        preg_match_all('/\bJKM\d{3}[A-Z]?N-[A-Z0-9\-()]+/i', $text, $matches);

        return array_values(array_unique($matches[0] ?? []));
    }

    private function extractValues(string $text): array
    {
        preg_match_all(
            '/-?\d+(?:[.,]\d+)?\s*(?:Wp|W|V|A|%|°C|℃|C)?/iu',
            $text,
            $matches
        );

        $values = [];

        foreach (($matches[0] ?? []) as $raw) {
            $raw = trim($raw);

            if (! preg_match('/(-?\d+(?:[.,]\d+)?)(?:\s*(Wp|W|V|A|%|°C|℃|C))?/iu', $raw, $parts)) {
                continue;
            }

            $values[] = [
                'raw' => $raw,
                'value' => (float) str_replace(',', '.', $parts[1]),
                'unit' => $parts[2] ?? null,
            ];
        }

        return $values;
    }

    private function rowDefinitions(): array
    {
        return [
            [
                'label' => 'Maximum Power (Pmax)',
                'pattern' => '/Maximum Power\s*\(Pmax\).*?(?=Maximum Power Voltage|Maximum Power Current|Open-circuit Voltage|Short-circuit Current|Module Efficiency|Operating Temperature)/isu',
            ],
            [
                'label' => 'Maximum Power Voltage (Vmp)',
                'pattern' => '/Maximum Power Voltage\s*\(Vmp\).*?(?=Maximum Power Current|Open-circuit Voltage|Short-circuit Current|Module Efficiency|Operating Temperature)/isu',
            ],
            [
                'label' => 'Maximum Power Current (Imp)',
                'pattern' => '/Maximum Power Current\s*\(Imp\).*?(?=Open-circuit Voltage|Short-circuit Current|Module Efficiency|Operating Temperature)/isu',
            ],
            [
                'label' => 'Open-circuit Voltage (Voc)',
                'pattern' => '/Open-circuit Voltage\s*\(Voc\).*?(?=Short-circuit Current|Module Efficiency|Operating Temperature)/isu',
            ],
            [
                'label' => 'Short-circuit Current (Isc)',
                'pattern' => '/Short-circuit Current\s*\(Isc\).*?(?=Module Efficiency|Operating Temperature)/isu',
            ],
            [
                'label' => 'Module Efficiency STC (%)',
                'pattern' => '/Module Efficiency STC\s*\(%\).*?(?=Operating Temperature|Maximum system voltage|Maximum series fuse rating|Power tolerance)/isu',
            ],
        ];
    }
}
