<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Fusion;

use App\DeviceScan\Processing\Document\Page;
use App\DeviceScan\Processing\Ocr\OcrBlock;
use App\DeviceScan\Processing\Ocr\OcrResult;
use App\DeviceScan\Processing\Tables\DTO\TableRegion;

final class HybridTableRegionDetector
{
    /**
     * @return TableRegion[]
     */
    public function detect(Page $page, OcrResult $ocr): array
    {
        if ($page->sections === []) {
            return [];
        }

        $regions = [];

        foreach ($page->sections as $section) {
            $type = strtolower((string) ($section->type ?? ''));

            if (! in_array($type, [
                'electrical',
                'mechanical',
                'specifications',
                'temperature',
                'packaging',
            ], true)) {
                continue;
            }

            $title = (string) ($section->title ?? $type);

            $left = (int) ($section->left ?? 0);
            $top = (int) ($section->top ?? 0);
            $width = (int) ($section->width ?? 0);
            $height = (int) ($section->height ?? 0);

            if ($width <= 0 || $height <= 0) {
                $bounds = $this->boundsFromNativeWords($page);

                if ($bounds === null) {
                    continue;
                }

                [$left, $top, $width, $height] = $bounds;
            }

            $block = new OcrBlock(
                text: $title,
                left: $left,
                top: $top,
                width: $width,
                height: $height,
                lines: [],
                confidence: null,
                metadata: [
                    'synthetic' => true,
                    'source' => 'hybrid_table_region_detector',
                    'section_type' => $type,
                ],
            );

            $regions[] = new TableRegion(
                type: $type,
                left: $left,
                top: $top,
                width: $width,
                height: $height,
                block: $block,
                metadata: [
                    'source' => 'hybrid_section_fallback',
                    'title' => $title,
                ],
            );
        }

        return $regions;
    }

    /**
     * @return array{0:int,1:int,2:int,3:int}|null
     */
    private function boundsFromNativeWords(Page $page): ?array
    {
        $words = $page->text?->words ?? [];

        if ($words === []) {
            return null;
        }

        $lefts = [];
        $tops = [];
        $rights = [];
        $bottoms = [];

        foreach ($words as $word) {
            $left = (float) ($word['left'] ?? 0);
            $top = (float) ($word['top'] ?? 0);
            $width = (float) ($word['width'] ?? 0);
            $height = (float) ($word['height'] ?? 0);

            if ($width <= 0 || $height <= 0) {
                continue;
            }

            $lefts[] = $left;
            $tops[] = $top;
            $rights[] = $left + $width;
            $bottoms[] = $top + $height;
        }

        if ($lefts === []) {
            return null;
        }

        $left = (int) floor(min($lefts));
        $top = (int) floor(min($tops));
        $right = (int) ceil(max($rights));
        $bottom = (int) ceil(max($bottoms));

        return [
            $left,
            $top,
            max(1, $right - $left),
            max(1, $bottom - $top),
        ];
    }
}