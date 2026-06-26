<?php

namespace App\DeviceScan\Extraction\Pdf;

class PdfPage
{
    public function __construct(
        public readonly int $pageNumber,
        public readonly string $text,
    ) {}

    public function toArray(): array
    {
        return [
            'page_number' => $this->pageNumber,
            'text' => $this->text,
        ];
    }
}