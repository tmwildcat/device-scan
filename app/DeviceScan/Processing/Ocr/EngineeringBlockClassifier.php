<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Ocr;

final class EngineeringBlockClassifier
{
    public function classify(OcrBlock $block): OcrBlock
    {
        $scores = [
            'electrical' => $this->score($block->text, [
                'electrical',
                'pmax',
                'vmp',
                'imp',
                'voc',
                'isc',
                'maximum power',
                'open circuit voltage',
                'short circuit current',
            ]),
            'mechanical' => $this->score($block->text, [
                'mechanical',
                'dimensions',
                'weight',
                'frame',
                'glass',
                'junction box',
                'cable',
                'cell type',
            ]),
            'temperature' => $this->score($block->text, [
                'temperature',
                'coefficient',
                'noct',
                'nmot',
            ]),
            'packaging' => $this->score($block->text, [
                'packaging',
                'packing',
                'pallet',
                'container',
            ]),
        ];

        arsort($scores);

        $type = array_key_first($scores);
        $score = $scores[$type] ?? 0;

        return new OcrBlock(
            text: $block->text,
            left: $block->left,
            top: $block->top,
            width: $block->width,
            height: $block->height,
            lines: $block->lines,
            confidence: $block->confidence,
            metadata: [
                ...$block->metadata,
                'engineering_type' => $score >= 2 ? $type : 'unknown',
                'engineering_score' => $score,
                'engineering_scores' => $scores,
            ],
        );
    }

    private function score(string $text, array $keywords): int
    {
        $normalized = mb_strtolower($text);

        $score = 0;

        foreach ($keywords as $keyword) {
            if (str_contains($normalized, mb_strtolower($keyword))) {
                $score++;
            }
        }

        return $score;
    }
}