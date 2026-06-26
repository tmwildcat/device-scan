<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing;

use App\DeviceScan\Processing\Document\Page;
use App\DeviceScan\Processing\Document\PageText;
use App\DeviceScan\Processing\Document\SourceDocument;
use Smalot\PdfParser\Document;
use Throwable;

final class TextExtractor
{
    public function extract(SourceDocument $document): SourceDocument
    {
        $pdf = $document->artifacts['pdf'] ?? null;

        if (! $pdf instanceof Document) {
            return $this->withWarning($document, 'Parsed PDF artifact missing; text extraction skipped.');
        }

        try {
            $pages = $pdf->getPages();
        } catch (Throwable $e) {
            return $this->withWarning($document, 'PDF text extraction failed: '.$e->getMessage());
        }

        $processedPages = [];

        foreach ($pages as $index => $page) {
            $processedPages[] = new Page(
                number: $index + 1,
                text: new PageText(
                    content: trim($page->getText()),
                    ocr: false,
                    metadata: [
                        'source' => 'smalot_pdfparser',
                    ],
                ),
            );
        }

        if ($processedPages === []) {
            return $this->withWarning($document, 'No native PDF pages found; OCR may be required later.');
        }

        return new SourceDocument(
            filename: $document->filename,
            mimeType: $document->mimeType,
            pageCount: count($processedPages),
            pages: $processedPages,
            metadata: $document->metadata,
            warnings: $document->warnings,
            artifacts: $document->artifacts,
        );
    }

    private function withWarning(SourceDocument $document, string $warning): SourceDocument
    {
        return new SourceDocument(
            filename: $document->filename,
            mimeType: $document->mimeType,
            pageCount: $document->pageCount,
            pages: $document->pages,
            metadata: $document->metadata,
            warnings: [
                ...$document->warnings,
                $warning,
            ],
            artifacts: $document->artifacts,
        );
    }
}