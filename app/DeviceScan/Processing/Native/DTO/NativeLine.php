<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Native\DTO;

final readonly class NativeLine
{
    /**
     * @param NativeWord[] $words
     */
    public function __construct(
        public int $index,
        public string $text,
        public float $left,
        public float $top,
        public float $width,
        public float $height,
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
}