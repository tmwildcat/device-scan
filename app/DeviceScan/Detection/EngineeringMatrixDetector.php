<?php

namespace App\DeviceScan\Detection;

use App\DeviceScan\Document\EngineeringMatrix;
use App\DeviceScan\Document\EngineeringMatrixCell;
use App\DeviceScan\Document\EngineeringMatrixRow;
use App\DeviceScan\Extraction\Pdf\PdfTextResult;
use App\DeviceScan\Document\EngineeringColumn;

class EngineeringMatrixDetector
{
    /**
     * @return EngineeringMatrix[]
     */
    public function detect(PdfTextResult $pdfTextResult): array
    {
        $matrices = [];

        foreach ($pdfTextResult->pages as $page) {
            $text = $page->text;

            if (! str_contains(strtolower($text), 'specifications')) {
                continue;
            }

            $headerParser = app(\App\DeviceScan\Parsing\Module\ModuleTypeHeaderParser::class);

            $models = $headerParser->parseModels($text);
            $conditions = $headerParser->parseConditions($text);

            $rows = [];

            foreach ($this->rowDefinitions() as $definition) {
                $row = $this->detectRow($text, $definition);

                if ($row) {
                    $rows[] = $row;
                }
            }

            if (count($rows) >= 3) {
                $matrices[] = new EngineeringMatrix(
                    page: $page->pageNumber,
                    models: $models,
                    columns: $this->buildColumns(
                        $models,
                        $conditions,
                    ),
                    rows: $rows,
                    title: 'SPECIFICATIONS',
                    metadata: [
                        'detector' => self::class,
                        'model_count' => count($models),
                    ],
                );
            }
        }

        return $matrices;
    }

    private function detectModels(string $text): array
    {
        preg_match_all('/JKM\d{3}[A-Z]?N-\d+[A-Z0-9-]*/i', $text, $matches);

        return array_values(array_unique($matches[0] ?? []));
    }

   private function buildColumns(
        array $models,
        array $conditions
    ): array {

        $columns = [];

        $index = 0;

        foreach ($models as $model) {

            foreach ($conditions as $condition) {

                $columns[] = new EngineeringColumn(
                    index: $index,
                    model: $model,
                    condition: $condition,
                );

                $index++;
            }
        }

        return $columns;
    }

    private function detectRow(string $text, array $definition): ?EngineeringMatrixRow
    {
        if (! preg_match($definition['pattern'], $text, $match)) {
            return null;
        }

        $raw = trim($match[0]);
        $values = $this->extractValues($raw);

        if ($values === []) {
            return null;
        }

        $cells = [];

        foreach ($values as $index => $value) {
            $cells[] = new EngineeringMatrixCell(
                text: $value['raw'],
                column: $index + 1,
                numericValue: $value['value'],
                unit: $value['unit'],
            );
        }

        return new EngineeringMatrixRow(
            label: $definition['label'],
            cells: $cells,
            canonicalField: $definition['field'],
            metadata: [
                'raw' => $raw,
                'pattern' => $definition['pattern'],
            ],
        );
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
                'field' => 'PNom',
                'label' => 'Maximum Power (Pmax)',
                'pattern' => '/Maximum Power\s*\(Pmax\).*?(?=Maximum Power Voltage|Maximum Power Current|Open-circuit Voltage|Short-circuit Current|Module Efficiency|Operating Temperature)/isu',
            ],
            [
                'field' => 'Vmp',
                'label' => 'Maximum Power Voltage (Vmp)',
                'pattern' => '/Maximum Power Voltage\s*\(Vmp\).*?(?=Maximum Power Current|Open-circuit Voltage|Short-circuit Current|Module Efficiency|Operating Temperature)/isu',
            ],
            [
                'field' => 'Imp',
                'label' => 'Maximum Power Current (Imp)',
                'pattern' => '/Maximum Power Current\s*\(Imp\).*?(?=Open-circuit Voltage|Short-circuit Current|Module Efficiency|Operating Temperature)/isu',
            ],
            [
                'field' => 'Voc',
                'label' => 'Open-circuit Voltage (Voc)',
                'pattern' => '/Open-circuit Voltage\s*\(Voc\).*?(?=Short-circuit Current|Module Efficiency|Operating Temperature)/isu',
            ],
            [
                'field' => 'Isc',
                'label' => 'Short-circuit Current (Isc)',
                'pattern' => '/Short-circuit Current\s*\(Isc\).*?(?=Module Efficiency|Operating Temperature)/isu',
            ],
            [
                'field' => 'modEff',
                'label' => 'Module Efficiency STC (%)',
                'pattern' => '/Module Efficiency STC\s*\(%\).*?(?=Operating Temperature|Maximum system voltage|Maximum series fuse rating|Power tolerance)/isu',
            ],
        ];
    }
}