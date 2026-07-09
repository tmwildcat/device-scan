<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Ocr;

final class OcrLayoutAnalyzer
{
    /**
     * @return OcrLine[]
     */
    public function lines(OcrResult $result): array
    {
        $words = array_values(array_filter(
            $result->words,
            fn (OcrWord $word) => trim($word->text) !== '',
        ));

        usort(
            $words,
            fn (OcrWord $a, OcrWord $b) =>
                ($a->top <=> $b->top) ?: ($a->left <=> $b->left)
        );

        $lines = [];

        foreach ($words as $word) {
            $matched = false;

            foreach ($lines as $index => $lineWords) {
                if ($this->belongsToLine($word, $lineWords)) {
                    $lines[$index][] = $word;
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                $lines[] = [$word];
            }
        }

        return array_map(
            fn (array $lineWords) => $this->makeLine($lineWords),
            $lines,
        );
    }

    /**
     * @param OcrWord[] $lineWords
     */
    private function belongsToLine(OcrWord $word, array $lineWords): bool
    {
        $lineTop = min(array_map(fn (OcrWord $w) => $w->top, $lineWords));
        $lineBottom = max(array_map(fn (OcrWord $w) => $w->bottom(), $lineWords));
        $lineMid = ($lineTop + $lineBottom) / 2;

        $wordMid = ($word->top + $word->bottom()) / 2;

        $avgHeight = array_sum(array_map(fn (OcrWord $w) => $w->height, $lineWords)) / count($lineWords);

        $tolerance = max(8, $avgHeight * 0.55);

        return abs($wordMid - $lineMid) <= $tolerance;
    }

    /**
     * @param OcrWord[] $words
     */
    private function makeLine(array $words): OcrLine
    {
        usort(
            $words,
            fn (OcrWord $a, OcrWord $b) => $a->left <=> $b->left,
        );

        $left = min(array_map(fn (OcrWord $word) => $word->left, $words));
        $top = min(array_map(fn (OcrWord $word) => $word->top, $words));
        $right = max(array_map(fn (OcrWord $word) => $word->right(), $words));
        $bottom = max(array_map(fn (OcrWord $word) => $word->bottom(), $words));

        $confidenceValues = array_values(array_filter(
            array_map(fn (OcrWord $word) => $word->confidence, $words),
            fn ($confidence) => is_numeric($confidence),
        ));

        return new OcrLine(
            text: $this->joinWords($words),
            left: $left,
            top: $top,
            width: $right - $left,
            height: $bottom - $top,
            words: $words,
            confidence: $confidenceValues === []
                ? null
                : array_sum($confidenceValues) / count($confidenceValues),
        );
    }

    /**
     * @param OcrWord[] $words
     */
    private function joinWords(array $words): string
    {
        $parts = [];

        foreach ($words as $index => $word) {
            if ($index === 0) {
                $parts[] = $word->text;
                continue;
            }

            $previous = $words[$index - 1];
            $gap = $word->left - $previous->right();

            $parts[] = $gap > max(18, $previous->height * 0.9)
                ? '    '.$word->text
                : $word->text;
        }

        return trim(implode(' ', $parts));
    }
}