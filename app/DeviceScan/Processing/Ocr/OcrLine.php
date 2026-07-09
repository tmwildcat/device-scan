<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Ocr;

final readonly class OcrLine
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
        public ?float $confidence = null,
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
            'words' => array_map(
                fn (OcrWord $word) => $word->toArray(),
                $this->words,
            ),
        ];
    }
}