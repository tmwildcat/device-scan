<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Geometry;

use App\DeviceScan\Processing\Ocr\OcrWord;

final readonly class TextRun
{
    /**
     * @param OcrWord[] $words
     */
    public function __construct(
        public string $text,
        public int $left,
        public int $top,
        public int $width,
        public int $height,
        public array $words = [],
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
            'metadata' => $this->metadata,
            'words' => array_map(
                fn (OcrWord $word) => $word->toArray(),
                $this->words,
            ),
        ];
    }
}