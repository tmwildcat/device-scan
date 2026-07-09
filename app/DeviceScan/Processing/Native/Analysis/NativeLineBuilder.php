<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Native\Analysis;

use App\DeviceScan\Processing\Native\DTO\NativeLine;
use App\DeviceScan\Processing\Native\DTO\NativeWord;

final class NativeLineBuilder
{
    /**
     * @param NativeWord[] $words
     * @return NativeLine[]
     */
    public function build(array $words): array
    {
        $words = array_values(array_filter(
            $words,
            fn (NativeWord $word) => trim($word->text) !== '',
        ));

        usort(
            $words,
            fn (NativeWord $a, NativeWord $b) => ($a->top <=> $b->top) ?: ($a->left <=> $b->left),
        );

        $buckets = [];

        foreach ($words as $word) {
            $placed = false;
            $centerY = $word->top + ($word->height / 2);

            foreach ($buckets as &$bucket) {
                $bucketCenter = $this->bucketCenterY($bucket);

                if (abs($centerY - $bucketCenter) <= max(3.0, $word->height * 0.55)) {
                    $bucket[] = $word;
                    $placed = true;
                    break;
                }
            }

            unset($bucket);

            if (! $placed) {
                $buckets[] = [$word];
            }
        }

        $lines = [];

        foreach ($buckets as $index => $bucket) {
            usort(
                $bucket,
                fn (NativeWord $a, NativeWord $b) => $a->left <=> $b->left,
            );

            $left = min(array_map(fn (NativeWord $word) => $word->left, $bucket));
            $top = min(array_map(fn (NativeWord $word) => $word->top, $bucket));
            $right = max(array_map(fn (NativeWord $word) => $word->right(), $bucket));
            $bottom = max(array_map(fn (NativeWord $word) => $word->bottom(), $bucket));

            $lines[] = new NativeLine(
                index: $index,
                text: trim(implode(' ', array_map(fn (NativeWord $word) => $word->text, $bucket))),
                left: $left,
                top: $top,
                width: $right - $left,
                height: $bottom - $top,
                words: $bucket,
                metadata: [
                    'source' => self::class,
                    'word_count' => count($bucket),
                ],
            );
        }

        return $lines;
    }

    /**
     * @param NativeWord[] $words
     */
    private function bucketCenterY(array $words): float
    {
        $tops = array_map(fn (NativeWord $word) => $word->top, $words);
        $bottoms = array_map(fn (NativeWord $word) => $word->bottom(), $words);

        return (min($tops) + max($bottoms)) / 2;
    }
}