<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\DTO;

final readonly class ModuleDetectedSectionDto
{
    public function __construct(
        public string $type,
        public string $title,
        public int $page,
        public int $startLine,
        public int $endLine,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'page' => $this->page,
            'start_line' => $this->startLine,
            'end_line' => $this->endLine,
            'metadata' => $this->metadata,
        ];
    }
}
