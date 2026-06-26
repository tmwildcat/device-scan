<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Document;

final readonly class PageText
{
    public function __construct(
        public string $content,
        public bool $ocr = false,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'ocr' => $this->ocr,
            'metadata' => $this->metadata,
        ];
    }
}