<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Ocr;

final class OcrBlockBuilder
{
    /**
     * @param OcrLine[] $lines
     * @return OcrBlock[]
     */
    public function build(array $lines): array
    {
        if ($lines === []) {
            return [];
        }

        usort(
            $lines,
            fn (OcrLine $a, OcrLine $b) =>
                ($a->top <=> $b->top) ?: ($a->left <=> $b->left)
        );

        $blocks = [];
        $current = [];

        foreach ($lines as $line) {
            if ($current === []) {
                $current[] = $line;
                continue;
            }

            $previous = $current[array_key_last($current)];

            if ($this->belongsToSameBlock($previous, $line)) {
                $current[] = $line;
                continue;
            }

            $blocks[] = $this->makeBlock($current);
            $current = [$line];
        }

        if ($current !== []) {
            $blocks[] = $this->makeBlock($current);
        }

        return $blocks;
    }

    private function belongsToSameBlock(OcrLine $previous, OcrLine $line): bool
    {
        $verticalGap = $line->top - $previous->bottom();

        if ($verticalGap < 0) {
            return true;
        }

        $averageHeight = ($previous->height + $line->height) / 2;

        if ($verticalGap > max(22, $averageHeight * 1.4)) {
            return false;
        }

        $leftDelta = abs($line->left - $previous->left);

        return $leftDelta <= max(80, $previous->height * 4);
    }

    /**
     * @param OcrLine[] $lines
     */
    private function makeBlock(array $lines): OcrBlock
    {
        $left = min(array_map(fn (OcrLine $line) => $line->left, $lines));
        $top = min(array_map(fn (OcrLine $line) => $line->top, $lines));
        $right = max(array_map(fn (OcrLine $line) => $line->right(), $lines));
        $bottom = max(array_map(fn (OcrLine $line) => $line->bottom(), $lines));

        $confidenceValues = array_values(array_filter(
            array_map(fn (OcrLine $line) => $line->confidence, $lines),
            fn ($confidence) => is_numeric($confidence),
        ));

        return new OcrBlock(
            text: implode("\n", array_map(fn (OcrLine $line) => $line->text, $lines)),
            left: $left,
            top: $top,
            width: $right - $left,
            height: $bottom - $top,
            lines: $lines,
            confidence: $confidenceValues === []
                ? null
                : array_sum($confidenceValues) / count($confidenceValues),
        );
    }
}