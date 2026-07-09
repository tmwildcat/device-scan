<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Sections;

final readonly class DetectedSection
{
    /**
     * @param string[] $lines
     */
    public function __construct(
        public string $type,
        public string $title,
        public int $page,
        public int $startLine,
        public int $endLine,
        public array $lines = [],
        public array $metadata = [],
    ) {}

    public function content(): string
    {
        return implode("\n", $this->lines);
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'page' => $this->page,
            'start_line' => $this->startLine,
            'end_line' => $this->endLine,
            'lines' => $this->lines,
            'content' => $this->content(),
            'metadata' => $this->metadata,
        ];
    }
}