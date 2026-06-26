<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Document;

final readonly class Page
{
    public function __construct(
        public int $number,
        public ?PageText $text = null,
        public ?string $imageUrl = null,
        public array $tables = [],
        public array $images = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'text' => $this->text?->toArray(),
            'image_url' => $this->imageUrl,
            'tables' => $this->tables,
            'images' => $this->images,
            'metadata' => $this->metadata,
        ];
    }
}