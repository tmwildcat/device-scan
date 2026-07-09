<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Geometry;

use App\DeviceScan\Processing\Ocr\OcrLine;
use App\DeviceScan\Processing\Ocr\OcrWord;

final class TextRunBuilder
{
    /**
     * @param OcrLine[] $lines
     * @return TextRun[]
     */
    public function build(array $lines): array
    {
        $runs = [];

        foreach ($lines as $line) {
            $lineRuns = $this->runsForLine($line);

            foreach ($lineRuns as $run) {
                $runs[] = $run;
            }
        }

        return $runs;
    }

    /**
     * @return TextRun[]
     */
    private function runsForLine(OcrLine $line): array
    {
        $words = array_values(array_filter(
            $line->words,
            fn (OcrWord $word) => trim($word->text) !== '',
        ));

        if ($words === []) {
            return [];
        }

        usort(
            $words,
            fn (OcrWord $a, OcrWord $b) => $a->left <=> $b->left,
        );

        $runs = [];
        $current = [];

        foreach ($words as $word) {
            if ($current === []) {
                $current[] = $word;
                continue;
            }

            $previous = $current[array_key_last($current)];

            if ($this->belongsToSameRun($previous, $word)) {
                $current[] = $word;
                continue;
            }

            $runs[] = $this->makeRun($current);
            $current = [$word];
        }

        if ($current !== []) {
            $runs[] = $this->makeRun($current);
        }

        return $runs;
    }

    private function belongsToSameRun(OcrWord $previous, OcrWord $word): bool
    {
        $gap = $word->left - $previous->right();

        if ($gap < 0) {
            return true;
        }

        $avgHeight = ($previous->height + $word->height) / 2;

        // Small normal word gap => same phrase/cell.
        if ($gap <= max(22, $avgHeight * 1.25)) {
            return true;
        }

        // Large gap => likely next table cell/column.
        return false;
    }

    /**
     * @param OcrWord[] $words
     */
    private function makeRun(array $words): TextRun
    {
        usort(
            $words,
            fn (OcrWord $a, OcrWord $b) => $a->left <=> $b->left,
        );

        $left = min(array_map(fn (OcrWord $word) => $word->left, $words));
        $top = min(array_map(fn (OcrWord $word) => $word->top, $words));
        $right = max(array_map(fn (OcrWord $word) => $word->right(), $words));
        $bottom = max(array_map(fn (OcrWord $word) => $word->bottom(), $words));

        return new TextRun(
            text: trim(implode(' ', array_map(fn (OcrWord $word) => $word->text, $words))),
            left: $left,
            top: $top,
            width: $right - $left,
            height: $bottom - $top,
            words: $words,
        );
    }
}