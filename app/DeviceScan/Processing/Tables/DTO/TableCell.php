<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\DTO;

final readonly class TableCell
{
    public function __construct(
        public int $row,
        public int $column,
        public string $text,
        public int $left,
        public int $top,
        public int $width,
        public int $height,
        public array $metadata = [],

        public ?string $ocrText = null,
        public ?string $nativeText = null,
        public string $textSource = 'ocr',
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
            'row' => $this->row,
            'column' => $this->column,
            'text' => $this->text,

            'ocr_text' => $this->ocrText,
            'native_text' => $this->nativeText,
            'text_source' => $this->textSource,

            'left' => $this->left,
            'top' => $this->top,
            'width' => $this->width,
            'height' => $this->height,
            'right' => $this->right(),
            'bottom' => $this->bottom(),
            'metadata' => $this->metadata,
        ];
    }
}