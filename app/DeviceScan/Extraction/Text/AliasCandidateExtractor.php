<?php

namespace App\DeviceScan\Extraction\Text;

use App\DeviceScan\Metadata\CandidateCollection;
use App\DeviceScan\Metadata\CandidateValue;
use App\DeviceScan\Metadata\DeviceMetadata;
use App\DeviceScan\Metadata\MetadataResult;
use App\DeviceScan\Metadata\SourceType;
use App\DeviceScan\Extraction\Pdf\PdfTextResult;

class AliasCandidateExtractor
{
    public function extract(string $deviceType, PdfTextResult $pdfTextResult): MetadataResult
    {
        $startedAt = microtime(true);

        $fields = DeviceMetadata::fieldsFor($deviceType);
        $candidates = new CandidateCollection();

        foreach ($pdfTextResult->pages as $page) {
            $lines = preg_split('/\R/u', $page->text) ?: [];

            foreach ($lines as $lineIndex => $line) {
                $normalizedLine = $this->normalize($line);

                if ($normalizedLine === '') {
                    continue;
                }

                foreach ($fields as $field) {
                    foreach ($field->aliases as $alias) {
                        if (! str_contains($normalizedLine, $this->normalize($alias))) {
                            continue;
                        }

                        $parsed = $this->extractValueAndUnit($line, $field->unit);

                        if ($parsed['value'] === null) {
                            continue;
                        }

                        $candidates->add(new CandidateValue(
                            field: $field->key,
                            value: $parsed['value'],
                            unit: $parsed['unit'] ?? $field->unit,
                            confidence: 0.72,
                            matchedAlias: $alias,
                            matchedPattern: 'alias-line-number',
                            rawText: trim($line),
                            page: $page->pageNumber,
                            line: $lineIndex + 1,
                            source: SourceType::PdfText->value,
                            metadata: [
                                'device_type' => $deviceType,
                            ],
                        ));
                    }
                }
            }
        }

        return new MetadataResult(
            candidates: $candidates,
            values: $this->bestValues($fields, $candidates),
            warnings: [],
            errors: [],
            pageCount: $pdfTextResult->pageCount,
            isNativePdf: $pdfTextResult->isNativePdf,
            rawText: $pdfTextResult->fullText,
            extractionTimeMs: (int) round((microtime(true) - $startedAt) * 1000),
            extractorName: self::class,
        );
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = str_replace(['-', '–', '—', ':', '(', ')', '[', ']'], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function extractValueAndUnit(string $line, ?string $expectedUnit = null): array
    {
        preg_match_all('/-?\d+(?:[.,]\d+)?\s*(?:Wp|W|kW|VDC|V|A|%|°C|C|kg|mm|years?)?/iu', $line, $matches);

        $tokens = $matches[0] ?? [];

        if ($tokens === []) {
            return ['value' => null, 'unit' => null];
        }

        $token = trim(end($tokens));

        if (! preg_match('/(-?\d+(?:[.,]\d+)?)(?:\s*([a-zA-Z%°]+))?/u', $token, $match)) {
            return ['value' => null, 'unit' => null];
        }

        $value = str_replace(',', '.', $match[1]);
        $unit = $match[2] ?? null;

        return [
            'value' => is_numeric($value) ? (float) $value : $value,
            'unit' => $unit ?: $expectedUnit,
        ];
    }

    private function bestValues(array $fields, CandidateCollection $candidates): array
    {
        $values = [];

        foreach ($fields as $field) {
            $best = $candidates->best($field->key);

            if ($best) {
                $values[$field->key] = $best->value;
            }
        }

        return $values;
    }
}