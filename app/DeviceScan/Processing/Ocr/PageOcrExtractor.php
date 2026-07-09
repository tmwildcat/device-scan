<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Ocr;

use App\DeviceScan\Processing\Document\Page;
use RuntimeException;
use Symfony\Component\Process\Process;

final class PageOcrExtractor
{
    public function extract(Page $page): ?OcrResult
    {
        $imagePath = $page->metadata['rendered_image_path'] ?? null;

        if (! is_string($imagePath) || ! is_file($imagePath)) {
            return null;
        }

        $process = new Process([
            'tesseract',
            $imagePath,
            'stdout',
            '--psm',
            '6',
            'tsv',
        ]);

        $process->setTimeout(60);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Tesseract OCR failed: '.$process->getErrorOutput());
        }

        return new OcrResult(
            page: $page->number,
            words: $this->parseTsv($process->getOutput()),
            metadata: [
                'source' => 'tesseract_tsv',
                'psm' => 6,
                'image_path' => $imagePath,
            ],
        );
    }

    /**
     * @return OcrWord[]
     */
    private function parseTsv(string $tsv): array
    {
        $lines = preg_split('/\R/u', trim($tsv)) ?: [];

        if (count($lines) <= 1) {
            return [];
        }

        $header = str_getcsv(array_shift($lines), "\t");
        $indexes = array_flip($header);

        $words = [];

        foreach ($lines as $line) {
            $cols = str_getcsv($line, "\t");

            $text = trim($cols[$indexes['text']] ?? '');

            if ($text === '') {
                continue;
            }

            $confidence = $cols[$indexes['conf']] ?? null;

            if ((float) $confidence < 0) {
                continue;
            }

            $words[] = new OcrWord(
                text: $text,
                left: (int) ($cols[$indexes['left']] ?? 0),
                top: (int) ($cols[$indexes['top']] ?? 0),
                width: (int) ($cols[$indexes['width']] ?? 0),
                height: (int) ($cols[$indexes['height']] ?? 0),
                confidence: is_numeric($confidence) ? (float) $confidence : null,
            );
        }

        return $words;
    }
}