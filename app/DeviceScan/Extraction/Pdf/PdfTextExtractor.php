<?php

namespace App\DeviceScan\Extraction\Pdf;

use Symfony\Component\Process\Process;

class PdfTextExtractor
{
    public function extract(string $absolutePdfPath): PdfTextResult
    {
        $process = new Process([
            'pdftotext',
            '-layout',
            '-enc',
            'UTF-8',
            $absolutePdfPath,
            '-',
        ]);

        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            return new PdfTextResult(
                fullText: '',
                pages: [],
                pageCount: 0,
                isNativePdf: false,
                warnings: [$process->getErrorOutput() ?: 'pdftotext failed.'],
            );
        }

        $text = trim($process->getOutput());

        $pages = $this->splitPages($text);

        return new PdfTextResult(
            fullText: $text,
            pages: $pages,
            pageCount: count($pages),
            isNativePdf: mb_strlen($text) > 100,
            warnings: [],
        );
    }

    /**
     * @return PdfPage[]
     */
    private function splitPages(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $rawPages = preg_split("/\f/", $text) ?: [$text];

        return collect($rawPages)
            ->map(fn (string $page) => trim($page))
            ->filter()
            ->values()
            ->map(fn (string $page, int $index) => new PdfPage(
                pageNumber: $index + 1,
                text: $page,
            ))
            ->all();
    }
}