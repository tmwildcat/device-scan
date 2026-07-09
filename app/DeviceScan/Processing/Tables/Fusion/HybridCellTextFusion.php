<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Fusion;

use App\DeviceScan\Processing\Document\Page;
use App\DeviceScan\Processing\Tables\DTO\TableCell;
use App\DeviceScan\Processing\Tables\DTO\TableGrid;
use App\DeviceScan\Processing\Native\DTO\NativeWord;

final class HybridCellTextFusion
{
    public function fuse(TableGrid $grid, Page $page): TableGrid
    {
        $nativeWords = $page->text?->words ?? [];

        if ($nativeWords === []) {
            return $grid;
        }

        $imageSize = $this->imageSize($page);

        if ($imageSize === null) {
            return $grid;
        }

        [$imageWidth, $imageHeight] = $imageSize;

        $cells = array_map(
            fn (TableCell $cell) => $this->fuseCell(
                cell: $cell,
                nativeWords: $nativeWords,
                imageWidth: $imageWidth,
                imageHeight: $imageHeight,
            ),
            $grid->cells,
        );

        return new TableGrid(
            type: $grid->type,
            columns: $grid->columns,
            rows: $grid->rows,
            cells: $cells,
            headerDetection: $grid->headerDetection,
            metadata: [
                ...$grid->metadata,
                'hybrid_cell_text_fusion' => true,
                'native_word_count' => count($nativeWords),
                'image_width' => $imageWidth,
                'image_height' => $imageHeight,
            ],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $nativeWords
     */
    private function fuseCell(
        TableCell $cell,
        array $nativeWords,
        int $imageWidth,
        int $imageHeight,
    ): TableCell {
        $inside = [];

        foreach ($nativeWords as $word) {
            $scaled = $this->scaleWord($word, $imageWidth, $imageHeight);

            if ($scaled === null) {
                continue;
            }

            if ($this->wordCenterInsideCell($scaled, $cell)) {
                $inside[] = $scaled;
            }
        }

        if ($inside === []) {
            return new TableCell(
                row: $cell->row,
                column: $cell->column,
                text: $cell->text,
                left: $cell->left,
                top: $cell->top,
                width: $cell->width,
                height: $cell->height,
                metadata: [
                    ...$cell->metadata,
                    'native_word_count' => 0,
                ],
                ocrText: $cell->ocrText ?? $cell->text,
                nativeText: null,
                textSource: $cell->textSource,
            );
        }

        usort(
            $inside,
            fn (array $a, array $b) => ($a['top'] <=> $b['top']) ?: ($a['left'] <=> $b['left'])
        );

        $nativeText = trim(implode(' ', array_map(
            fn (array $word) => $word['text'],
            $inside,
        )));

        if ($nativeText === '') {
            return $cell;
        }

        return new TableCell(
            row: $cell->row,
            column: $cell->column,
            text: $nativeText,
            left: $cell->left,
            top: $cell->top,
            width: $cell->width,
            height: $cell->height,
            metadata: [
                ...$cell->metadata,
                'native_word_count' => count($inside),
                'fusion_method' => 'native_pdf_scaled_center_inside_cell',
                'native_words' => array_map(
                    fn (array $word) => [
                        'text' => $word['text'],
                        'left' => $word['left'],
                        'top' => $word['top'],
                        'width' => $word['width'],
                        'height' => $word['height'],
                        'right' => $word['right'],
                        'bottom' => $word['bottom'],
                    ],
                    $inside,
                ),
            ],
            ocrText: $cell->ocrText ?? $cell->text,
            nativeText: $nativeText,
            textSource: 'native_pdf',
        );
    }

    private function scaleWord(NativeWord|array $word, int $imageWidth, int $imageHeight): ?array
{
    if ($word instanceof NativeWord) {
        $metadata = $word->metadata;

        $pdfWidth = (float) ($metadata['pdf_page_width'] ?? 0);
        $pdfHeight = (float) ($metadata['pdf_page_height'] ?? 0);

        if ($pdfWidth <= 0 || $pdfHeight <= 0) {
            return null;
        }

        $scaleX = $imageWidth / $pdfWidth;
        $scaleY = $imageHeight / $pdfHeight;

        $left = $word->left * $scaleX;
        $top = $word->top * $scaleY;
        $width = $word->width * $scaleX;
        $height = $word->height * $scaleY;

        return [
            'text' => trim($word->text),
            'left' => $left,
            'top' => $top,
            'width' => $width,
            'height' => $height,
            'right' => $left + $width,
            'bottom' => $top + $height,
            'scale_x' => $scaleX,
            'scale_y' => $scaleY,
        ];
    }

    $metadata = $word['metadata'] ?? [];

    $pdfWidth = (float) ($metadata['pdf_page_width'] ?? 0);
    $pdfHeight = (float) ($metadata['pdf_page_height'] ?? 0);

    if ($pdfWidth <= 0 || $pdfHeight <= 0) {
        return null;
    }

    $scaleX = $imageWidth / $pdfWidth;
    $scaleY = $imageHeight / $pdfHeight;

    $left = (float) ($word['left'] ?? 0) * $scaleX;
    $top = (float) ($word['top'] ?? 0) * $scaleY;
    $width = (float) ($word['width'] ?? 0) * $scaleX;
    $height = (float) ($word['height'] ?? 0) * $scaleY;

    return [
        'text' => trim((string) ($word['text'] ?? '')),
        'left' => $left,
        'top' => $top,
        'width' => $width,
        'height' => $height,
        'right' => $left + $width,
        'bottom' => $top + $height,
        'scale_x' => $scaleX,
        'scale_y' => $scaleY,
    ];
}

    private function wordCenterInsideCell(array $word, TableCell $cell): bool
    {
        $x = ($word['left'] + $word['right']) / 2;
        $y = ($word['top'] + $word['bottom']) / 2;

        return $x >= $cell->left
            && $x <= $cell->right()
            && $y >= $cell->top
            && $y <= $cell->bottom();
    }

    /**
     * @return array{0:int,1:int}|null
     */
    private function imageSize(Page $page): ?array
    {
        if (! is_string($page->imageUrl) || $page->imageUrl === '') {
            return null;
        }

        $path = $this->pathFromImageUrl($page->imageUrl);

        if ($path === null || ! is_file($path)) {
            return null;
        }

        $size = @getimagesize($path);

        if ($size === false) {
            return null;
        }

        return [(int) $size[0], (int) $size[1]];
    }

    private function pathFromImageUrl(string $imageUrl): ?string
    {
        $path = parse_url($imageUrl, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $storagePrefix = '/storage/';

        if (! str_starts_with($path, $storagePrefix)) {
            return null;
        }

        return storage_path('app/public/'.substr($path, strlen($storagePrefix)));
    }
}