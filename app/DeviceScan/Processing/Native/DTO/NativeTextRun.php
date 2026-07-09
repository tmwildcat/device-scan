<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Native\DTO;

use App\DeviceScan\Processing\Native\DTO\NativeWord;

final readonly class NativeTextRun
{
    /**
     * @param NativeWord[] $words
     */
    public function __construct(
        public string $text,
        public float $left,
        public float $top,
        public float $width,
        public float $height,
        public int $page,
        public array $words = [],
        public array $metadata = [],
    ) {}

    public function right(): float
    {
        return $this->left + $this->width;
    }

    public function bottom(): float
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
            'page' => $this->page,
            'metadata' => $this->metadata,
            'words' => array_map(
                fn (NativeWord $word) => $word->toArray(),
                $this->words,
            ),
        ];
    }
}