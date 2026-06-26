<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Document;

final readonly class SourceDocument
{
    /**
     * @param Page[] $pages
     * @param string[] $warnings
     */
    public function __construct(
        public string $filename,
        public string $mimeType,
        public ?int $pageCount = null,
        public array $pages = [],
        public array $metadata = [],
        public array $warnings = [],
        public array $artifacts = [],
    ) {}

    public function toArray(): array
    {
        return [
            'filename' => $this->filename,
            'mime_type' => $this->mimeType,
            'page_count' => $this->pageCount,
            'metadata' => $this->metadata,
            'warnings' => $this->warnings,
            'pages' => array_map(
                fn (Page $page) => $page->toArray(),
                $this->pages
            ),
        ];
    }
}