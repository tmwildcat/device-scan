<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing;

use App\DeviceScan\Processing\Document\Page;
use App\DeviceScan\Processing\Document\PageText;
use App\DeviceScan\Processing\Document\SourceDocument;
use Smalot\PdfParser\Document;
use Throwable;

use App\DeviceScan\Processing\Native\DTO\NativeWord;
use App\DeviceScan\Processing\Native\PopplerNativeWordExtractor;

final class TextExtractor
{
    public function __construct(
        private readonly PopplerNativeWordExtractor $nativeWordExtractor,
    ) {}
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

    $nativeWordsByPage = $this->nativeWordExtractor->extract($document);

    $processedPages = [];

    foreach ($pages as $index => $page) {
        $pageNumber = $index + 1;
        $words = $nativeWordsByPage[$pageNumber] ?? [];

        $processedPages[] = new Page(
            number: $pageNumber,
            text: new PageText(
                content: trim($page->getText()),
                ocr: false,
                words: $words,
                metadata: [
                    'source' => 'smalot_pdfparser',
                    'word_source' => 'poppler_pdftotext_bbox_layout',
                    'word_count' => count($words),
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
        metadata: [
            ...$document->metadata,
            'native_word_extraction' => [
                'source' => 'poppler_pdftotext_bbox_layout',
                'pages_with_words' => count(array_filter($nativeWordsByPage)),
            ],
        ],
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