<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Document;

use App\DeviceScan\Processing\Ocr\OcrResult;

final readonly class Page
{
    public function __construct(
        public int $number,
        public ?PageText $text = null,
        public ?string $imageUrl = null,
        public array $tables = [],
        public array $images = [],
        public array $sections = [],
        public ?OcrResult $ocr = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'text' => $this->text?->toArray(),
            'image_url' => $this->imageUrl,
            'tables' => array_map(
                fn ($table) => method_exists($table, 'toArray') ? $table->toArray() : $table,
                $this->tables,
            ),
            'images' => array_map(
                fn ($image) => method_exists($image, 'toArray') ? $image->toArray() : $image,
                $this->images,
            ),
            'sections' => array_map(
                fn ($section) => method_exists($section, 'toArray') ? $section->toArray() : $section,
                $this->sections,
            ),
            'ocr' => $this->ocr?->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}