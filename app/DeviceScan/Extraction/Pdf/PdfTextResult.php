<?php

namespace App\DeviceScan\Extraction\Pdf;

class PdfTextResult
{
    /**
     * @param  PdfPage[]  $pages
     */
    public function __construct(
        public readonly string $fullText,
        public readonly array $pages,
        public readonly int $pageCount,
        public readonly bool $isNativePdf,
        public readonly array $warnings = [],
    ) {}

    public function toArray(): array
    {
        return [
            'full_text' => $this->fullText,
            'pages' => array_map(fn (PdfPage $page) => $page->toArray(), $this->pages),
            'page_count' => $this->pageCount,
            'is_native_pdf' => $this->isNativePdf,
            'warnings' => $this->warnings,
        ];
    }
}