<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\DTO;

use App\DeviceScan\Processing\Ocr\OcrBlock;

final readonly class TableRegion
{
    public function __construct(
        public string $type,
        public int $left,
        public int $top,
        public int $width,
        public int $height,
        public OcrBlock $block,
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
            'type' => $this->type,
            'left' => $this->left,
            'top' => $this->top,
            'width' => $this->width,
            'height' => $this->height,
            'right' => $this->right(),
            'bottom' => $this->bottom(),
            'metadata' => $this->metadata,
            'block' => $this->block->toArray(),
        ];
    }
}