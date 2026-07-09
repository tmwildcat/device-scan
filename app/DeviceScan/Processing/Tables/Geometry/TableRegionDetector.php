<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Geometry;

use App\DeviceScan\Processing\Ocr\OcrBlock;
use App\DeviceScan\Processing\Ocr\OcrResult;
use App\DeviceScan\Processing\Tables\DTO\TableRegion;

final class TableRegionDetector
{
    /**
     * @return TableRegion[]
     */
    public function detect(OcrResult $ocr): array
    {
        $regions = [];

        foreach ($ocr->blocks as $block) {
            $type = $block->metadata['engineering_type'] ?? 'unknown';
            $score = (int) ($block->metadata['engineering_score'] ?? 0);

            if (! in_array($type, ['electrical', 'mechanical', 'temperature'], true)) {
                continue;
            }

            if ($score < 2) {
                continue;
            }

            if (! $this->looksLikeTable($block)) {
                continue;
            }

            $regions[] = new TableRegion(
                type: $type,
                left: $block->left,
                top: $block->top,
                width: $block->width,
                height: $block->height,
                block: $block,
                metadata: [
                    'source' => self::class,
                    'engineering_score' => $score,
                ],
            );
        }

        return $regions;
    }

    private function looksLikeTable(OcrBlock $block): bool
    {
        if (count($block->lines) < 2) {
            return false;
        }

        $text = mb_strtolower($block->text);

        if (preg_match('/pmax|voc|vmp|isc|imp|maximum power|open circuit|short circuit|module efficiency/u', $text)) {
            return true;
        }

        if (preg_match('/dimensions|weight|junction box|frame|glass|cable|cell type/u', $text)) {
            return true;
        }

        $numericLines = 0;

        foreach ($block->lines as $line) {
            if (preg_match_all('/\d+(?:[.,]\d+)?/u', $line->text) >= 2) {
                $numericLines++;
            }
        }

        return $numericLines >= 1;
    }
}
