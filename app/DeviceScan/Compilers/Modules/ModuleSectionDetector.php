<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules;

use App\DeviceScan\Processing\Document\Page;
use App\DeviceScan\Processing\Document\SourceDocument;
use App\DeviceScan\Processing\Sections\DetectedSection;

final class ModuleSectionDetector
{
    public const ELECTRICAL_STC = 'MODULE_ELECTRICAL_STC';
    public const ELECTRICAL_VARIANT = 'MODULE_ELECTRICAL_VARIANT';
    public const MECHANICAL = 'MODULE_MECHANICAL';
    public const OPERATING_CONDITIONS = 'MODULE_OPERATING_CONDITIONS';
    public const TEMPERATURE_CHARACTERISTICS = 'MODULE_TEMPERATURE_CHARACTERISTICS';

    public function detect(SourceDocument $document): SourceDocument
    {
        return new SourceDocument(
            filename: $document->filename,
            mimeType: $document->mimeType,
            pageCount: $document->pageCount,
            pages: array_map(
                fn (Page $page) => $this->detectOnPage($page),
                $document->pages,
            ),
            metadata: $document->metadata,
            warnings: $document->warnings,
            artifacts: $document->artifacts,
        );
    }

    private function detectOnPage(Page $page): Page
    {
        $text = $page->text?->content ?? '';

        if (trim($text) === '') {
            return $page;
        }

        $lines = array_values(array_map(
            fn (string $line) => trim($line),
            preg_split('/\R/u', $text) ?: [],
        ));

        $headings = $this->findHeadings($lines);
        $sections = [];

        foreach ($headings as $index => $heading) {
            $nextHeading = $headings[$index + 1] ?? null;
            $startLine = $heading['line'];
            $endLine = $nextHeading
                ? max($startLine, $nextHeading['line'] - 1)
                : count($lines) - 1;

            $sections[] = new DetectedSection(
                type: $heading['type'],
                title: $heading['title'],
                page: $page->number,
                startLine: $startLine,
                endLine: $endLine,
                lines: array_values(array_filter(
                    array_slice($lines, $startLine, $endLine - $startLine + 1),
                    fn (string $line) => $line !== '',
                )),
                metadata: [
                    'detector' => self::class,
                    'matched_phrase' => $heading['phrase'],
                ],
            );
        }

        return new Page(
            number: $page->number,
            text: $page->text,
            imageUrl: $page->imageUrl,
            tables: $page->tables,
            images: $page->images,
            sections: $sections,
            ocr: $page->ocr,
            metadata: $page->metadata,
        );
    }

    private function findHeadings(array $lines): array
    {
        $headings = [];

        foreach ($lines as $index => $line) {
            if (! $this->looksLikeHeadingCandidate($line)) {
                continue;
            }

            $normalized = $this->normalize($line);

            foreach ($this->sectionDefinitions() as $type => $phrases) {
                foreach ($phrases as $phrase) {
                    if (! str_contains($normalized, $this->normalize($phrase))) {
                        continue;
                    }

                    $headings[] = [
                        'type' => $type,
                        'title' => $line,
                        'phrase' => $phrase,
                        'line' => $index,
                    ];

                    continue 3;
                }
            }
        }

        return $this->dedupeNearbyHeadings($headings);
    }

    private function looksLikeHeadingCandidate(string $line): bool
    {
        $line = trim($line);

        if ($line === '' || mb_strlen($line) > 180) {
            return false;
        }

        if (preg_match('/^(Maximum|Open[- ]?circuit|Short[- ]?circuit|Voltage at|Current at|Rated Max|Module Efficiency)/iu', $line)) {
            return false;
        }

        return true;
    }

    private function dedupeNearbyHeadings(array $headings): array
    {
        $result = [];

        foreach ($headings as $heading) {
            $previous = end($result);

            if (
                $previous
                && $previous['type'] === $heading['type']
                && abs($previous['line'] - $heading['line']) <= 2
            ) {
                continue;
            }

            $result[] = $heading;
        }

        return array_values($result);
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = str_replace(['–', '—', '‐', '‑'], '-', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function sectionDefinitions(): array
    {
        return [
            self::ELECTRICAL_VARIANT => [
                'electrical characteristics with',
                'different rear side power gain',
                'bifacial gain',
                'bnpi',
                'solar irradiation ratio',
            ],
            self::ELECTRICAL_STC => [
                'electrical parameters | stc',
                'electrical parameters at stc',
                'electrical characteristics stc',
                'electrical characteristics',
                'electrical data (stc',
                'electrical data - stc',
                'electrical data stc',
                'electrical data, front stc characteristics',
                'front stc characteristics',
                'ratings at standard test conditions',
                'electrical specifications',
                'electrical data product code',
                'all data measured to stc',
                'electrical data',
                'electrical performance',
            ],
            self::MECHANICAL => [
                'mechanical parameters',
                'mechanical data',
                'mechanical specifications',
                'mechanical characteristics',
                'mechanical description',
            ],
            self::OPERATING_CONDITIONS => [
                'operating conditions',
                'operating parameters',
                'maximum ratings',
                'operational temperature',
            ],
            self::TEMPERATURE_CHARACTERISTICS => [
                'temperature characteristics',
                'temperature ratings',
                'temperature dependence',
                'temperature coefficient',
            ],
        ];
    }
}
