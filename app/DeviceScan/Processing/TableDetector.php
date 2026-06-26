<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing;

use App\DeviceScan\Processing\Document\Page;
use App\DeviceScan\Processing\Document\SourceDocument;
use App\DeviceScan\Processing\Tables\DetectedTable;
use App\DeviceScan\Processing\Tables\DetectedTableCell;
use App\DeviceScan\Processing\Tables\DetectedTableRow;

use App\DeviceScan\Processing\Tables\Modules\ModuleElectricalMatrixExtractor;

final class TableDetector
{
    public function __construct(
        private readonly ModuleElectricalMatrixExtractor $moduleElectricalMatrixExtractor,
    ) {}
    private const TABLE_TITLES = [
        'electrical characteristics',
        'mechanical characteristics',
        'temperature characteristics',
        'technical data',
        'technical specification',
        'input data',
        'output data',
        'efficiency',
        'general data',
        'protection',
        'features',
        'interfaces',
    ];

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
        $text = $page->text?->content;

        if (! is_string($text) || trim($text) === '') {
            return $page;
        }

        $moduleTable = $this->moduleElectricalMatrixExtractor->extract($page);

        if ($moduleTable !== null) {
            return new Page(
                number: $page->number,
                text: $page->text,
                imageUrl: $page->imageUrl,
                tables: [$moduleTable],
                images: $page->images,
                metadata: $page->metadata,
            );
        }

        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\R/u', $text) ?: []),
            fn (string $line) => $line !== '',
        ));

        $title = $this->guessTitle($lines);

        $rows = [];

        foreach ($lines as $line) {
            if (! $this->looksLikeDataRow($line)) {
                continue;
            }

            $row = $this->parseRow($line);

            if ($row !== null) {
                $rows[] = $row;
            }
        }

        if (count($rows) < 3) {
            return $page;
        }

        $table = new DetectedTable(
            title: $title ?? 'Detected Table',
            page: $page->number,
            models: $this->guessModels($lines),
            rows: $rows,
            metadata: [
                'source' => 'line_based_detector_v1',
            ],
        );

        return new Page(
            number: $page->number,
            text: $page->text,
            tables: [$table],
            images: $page->images,
            metadata: $page->metadata,
        );
    }

    private function guessTitle(array $lines): ?string
    {
        foreach ($lines as $line) {
            $normalized = mb_strtolower($line);

            foreach (self::TABLE_TITLES as $title) {
                if (str_contains($normalized, $title)) {
                    return $line;
                }
            }
        }

        return null;
    }

    private function guessModels(array $lines): array
    {
        $models = [];

        foreach ($lines as $line) {
            preg_match_all('/\b[A-Z]{2,}[A-Z0-9\-\/\.]{2,}\b/u', $line, $matches);

            foreach ($matches[0] ?? [] as $match) {
                if (preg_match('/^(STC|NOCT|NMOT|IEC|UL|PDF|DC|AC|MPPT|MPP)$/i', $match)) {
                    continue;
                }

                $models[] = $match;
            }
        }

        return array_values(array_unique(array_slice($models, 0, 20)));
    }

    private function looksLikeDataRow(string $line): bool
    {
        $numberCount = preg_match_all(
            '/-?\d+(?:[.,]\d+)?\s*(?:Wp|W|kW|V|A|%|°C|℃|kg|mm|Hz|VA|kVA|years?)?/iu',
            $line
        );

        $hasKeyword = preg_match(
            '/power|voltage|current|efficiency|temperature|weight|dimension|frequency|protection|mppt|mpp|input|output|warranty|fuse|humidity|altitude|cooling|communication|connector/i',
            $line
        );

        return $numberCount >= 2 || ($numberCount >= 1 && (bool) $hasKeyword);
    }

    private function parseRow(string $line): ?DetectedTableRow
    {
        $parts = preg_split('/\s{2,}/u', trim($line)) ?: [];

        if (count($parts) < 2) {
            $parts = preg_split('/\t+/u', trim($line)) ?: [];
        }

        if (count($parts) < 2) {
            return null;
        }

        $label = array_shift($parts);

        return new DetectedTableRow(
            label: trim((string) $label),
            cells: array_map(
                fn (string $part) => $this->parseCell($part),
                $parts,
            ),
        );
    }

    private function parseCell(string $raw): DetectedTableCell
    {
        $display = trim($raw);

        $numeric = null;
        $unit = null;

        if (preg_match('/(-?\d+(?:[.,]\d+)?)\s*([a-zA-Z%°℃]+)?/u', $display, $match)) {
            $numeric = (float) str_replace(',', '.', $match[1]);
            $unit = $match[2] ?? null;
        }

        return new DetectedTableCell(
            value: $numeric ?? $display,
            displayValue: $display,
            unit: $unit,
        );
    }
}