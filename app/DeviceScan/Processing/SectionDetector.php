<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing;

use App\DeviceScan\Processing\Document\Page;
use App\DeviceScan\Processing\Document\SourceDocument;
use App\DeviceScan\Processing\Sections\DetectedSection;

final class SectionDetector
{
    public function detect(SourceDocument $document): SourceDocument
    {
        $pages = array_map(
            fn (Page $page) => $this->detectOnPage($page),
            $document->pages,
        );

        return new SourceDocument(
            filename: $document->filename,
            mimeType: $document->mimeType,
            pageCount: $document->pageCount,
            pages: $pages,
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

            $sectionLines = array_values(array_filter(
                array_slice($lines, $startLine, $endLine - $startLine + 1),
                fn (string $line) => $line !== '',
            ));

            $sections[] = new DetectedSection(
                type: $heading['type'],
                title: $heading['title'],
                page: $page->number,
                startLine: $startLine,
                endLine: $endLine,
                lines: $sectionLines,
                metadata: [
                    'detector' => self::class,
                    'matched_keyword' => $heading['keyword'],
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
            if ($line === '') {
                continue;
            }

            if (! $this->looksLikeHeading($line)) {
                continue;
            }

            $normalized = $this->normalize($line);

            foreach ($this->sectionDefinitions() as $type => $keywords) {
                foreach ($keywords as $keyword) {
                    if (! str_contains($normalized, $this->normalize($keyword))) {
                        continue;
                    }

                    $headings[] = [
                        'type' => $type,
                        'title' => $line,
                        'keyword' => $keyword,
                        'line' => $index,
                    ];

                    continue 3;
                }
            }
        }

        return $this->dedupeNearbyHeadings($headings);
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

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    private function sectionDefinitions(): array
    {
        return [
            'identity' => [
                'product',
                'series',
                'module type',
            ],

            'features' => [
                'key features',
                'features',
            ],

            'warranty' => [
                'linear performance warranty',
                'linear power warranty',
                'product warranty',
                'warranty',
            ],

            'specifications' => [
                'specifications',
                'technical data',
                'technical specification',
            ],

            'electrical' => [
                'electrical characteristics',
                'electrical performance',
                'electrical performance & temperature dependence',
            ],

            'mechanical' => [
                'mechanical characteristics',
                'mechanical specification',
                'engineering drawings',
            ],

            'temperature' => [
                'temperature characteristics',
                'temperature dependence',
            ],

            'packaging' => [
                'packing configuration',
                'packaging configuration',
                'packaging',
            ],

            'certification' => [
                'certifications',
                'certification',
                'standards',
            ],
        ];
    }

    private function looksLikeHeading(string $line): bool
    {
        $line = trim($line);

        if ($line === '') {
            return false;
        }

        if (mb_strlen($line) > 80) {
            return false;
        }

        if (preg_match('/\d+(?:[.,]\d+)?\s*(W|V|A|kg|mm|%|°C|℃|year|years)/iu', $line)) {
            return false;
        }

        if (preg_match('/^(Maximum|Open-circuit|Short-circuit|Temperature coefficients|Dimensions|Weight|Frame|Junction Box|Output Cables)/iu', $line)) {
            return false;
        }

        return true;
    }
}
