<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Native\Analysis;

use App\DeviceScan\Processing\Native\DTO\NativeTextRun;
use App\DeviceScan\Processing\Native\DTO\NativeWord;

final class NativeTextRunBuilder
{
    /**
     * @param NativeWord[] $words
     * @return NativeTextRun[]
     */
    public function build(array $words): array
    {
        $words = array_values(array_filter(
            $words,
            fn (NativeWord $word) => trim($word->text) !== '',
        ));

        if ($words === []) {
            return [];
        }

        usort(
            $words,
            fn (NativeWord $a, NativeWord $b) => ($a->top <=> $b->top) ?: ($a->left <=> $b->left)
        );

        $lines = $this->groupIntoLines($words);

        $runs = [];

        foreach ($lines as $lineWords) {
            foreach ($this->runsForLine($lineWords) as $run) {
                $runs[] = $run;
            }
        }

        return $runs;
    }

    /**
     * @param NativeWord[] $words
     * @return array<int, NativeWord[]>
     */
    private function groupIntoLines(array $words): array
    {
        $lines = [];

        foreach ($words as $word) {
            $placed = false;
            $wordCenterY = $word->top + ($word->height / 2);

            foreach ($lines as &$line) {
                $lineCenterY = $this->lineCenterY($line);
                $lineAvgHeight = $this->lineAverageHeight($line);

                if (abs($wordCenterY - $lineCenterY) <= max(3.0, $lineAvgHeight * 0.55)) {
                    $line[] = $word;
                    $placed = true;
                    break;
                }
            }

            unset($line);

            if (! $placed) {
                $lines[] = [$word];
            }
        }

        foreach ($lines as &$line) {
            usort(
                $line,
                fn (NativeWord $a, NativeWord $b) => $a->left <=> $b->left
            );
        }

        unset($line);

        return $lines;
    }

    /**
     * @param NativeWord[] $line
     */
    private function lineCenterY(array $line): float
    {
        $tops = array_map(fn (NativeWord $word) => $word->top, $line);
        $bottoms = array_map(fn (NativeWord $word) => $word->bottom(), $line);

        return (min($tops) + max($bottoms)) / 2;
    }

    /**
     * @param NativeWord[] $line
     */
    private function lineAverageHeight(array $line): float
    {
        return array_sum(array_map(fn (NativeWord $word) => $word->height, $line)) / max(1, count($line));
    }

    /**
     * @param NativeWord[] $words
     * @return NativeTextRun[]
     */
    private function runsForLine(array $words): array
    {
        if ($words === []) {
            return [];
        }

        usort(
            $words,
            fn (NativeWord $a, NativeWord $b) => $a->left <=> $b->left
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

    private function belongsToSameRun(NativeWord $previous, NativeWord $word): bool
    {
        $gap = $word->left - $previous->right();

        if ($gap < 0) {
            return true;
        }

        $avgHeight = ($previous->height + $word->height) / 2;

        return $gap <= max(6.0, $avgHeight * 1.25);
    }

    /**
     * @param NativeWord[] $words
     */
    private function makeRun(array $words): NativeTextRun
    {
        usort(
            $words,
            fn (NativeWord $a, NativeWord $b) => $a->left <=> $b->left
        );

        $left = min(array_map(fn (NativeWord $word) => $word->left, $words));
        $top = min(array_map(fn (NativeWord $word) => $word->top, $words));
        $right = max(array_map(fn (NativeWord $word) => $word->right(), $words));
        $bottom = max(array_map(fn (NativeWord $word) => $word->bottom(), $words));

        return new NativeTextRun(
            text: trim(implode(' ', array_map(fn (NativeWord $word) => $word->text, $words))),
            left: $left,
            top: $top,
            width: $right - $left,
            height: $bottom - $top,
            page: $words[0]->page,
            words: $words,
            metadata: [
                'source' => 'native_pdf',
                'word_count' => count($words),
            ],
        );
    }
}