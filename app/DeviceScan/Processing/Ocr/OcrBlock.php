<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Ocr;

final readonly class OcrBlock
{
    /**
     * @param OcrLine[] $lines
     */
    public function __construct(
        public string $text,
        public int $left,
        public int $top,
        public int $width,
        public int $height,
        public array $lines = [],
        public ?float $confidence = null,
        public array $metadata = [],
    ) {}

    public function right(): int
    {
        return $this->left + $this->width;
    }

    public function bottom(): int
    {
        return $this->top + $this->height;
    }

    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'left' => $this->left,
            'top' => $this->top,
            'width' => $this->width,
            'height' => $this->height,
            'right' => $this->right(),
            'bottom' => $this->bottom(),
            'confidence' => $this->confidence,
            'metadata' => $this->metadata,
            'lines' => array_map(
                fn (OcrLine $line) => $line->toArray(),
                $this->lines,
            ),
        ];
    }
}