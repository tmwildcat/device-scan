<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Native;

use App\DeviceScan\Processing\Document\SourceDocument;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

use App\DeviceScan\Processing\Native\DTO\NativeWord;

final class PopplerNativeWordExtractor
{
    /**
     * @return array<int, NativeWord[]>
     */
    public function extract(SourceDocument $document): array
    {
        $path = $document->metadata['path'] ?? null;

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            return [];
        }

        $dir = storage_path('app/private/device-scan/poppler');

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $outputPath = $dir.'/bbox-'.Str::uuid().'.xml';

        $process = new Process([
            'pdftotext',
            '-bbox-layout',
            $path,
            $outputPath,
        ]);

        $process->setTimeout(30);

        try {
            $process->run();

            if (! $process->isSuccessful() || ! is_file($outputPath)) {
                return [];
            }

            $xml = file_get_contents($outputPath);

            if (! is_string($xml) || trim($xml) === '') {
                return [];
            }

            return $this->parse($xml, $outputPath);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, NativeWord[]>
     */
    private function parse(string $xml, string $artifactPath): array
    {
        $dom = new \DOMDocument();

        $previous = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            return [];
        }

        $xpath = new \DOMXPath($dom);
        $pages = [];

        $pageNodes = $xpath->query('//*[local-name()="page"]');

        foreach ($pageNodes ?: [] as $pageIndex => $pageNode) {
            if (! $pageNode instanceof \DOMElement) {
                continue;
            }

            $pageNumber = $pageIndex + 1;
            $words = [];

            $pageWidth = (float) $pageNode->getAttribute('width');
            $pageHeight = (float) $pageNode->getAttribute('height');

            $wordNodes = $xpath->query('.//*[local-name()="word"]', $pageNode);

            foreach ($wordNodes ?: [] as $wordNode) {
                if (! $wordNode instanceof \DOMElement) {
                    continue;
                }

                $text = trim($wordNode->textContent);

                if ($text === '') {
                    continue;
                }

                $xMin = (float) $wordNode->getAttribute('xMin');
                $yMin = (float) $wordNode->getAttribute('yMin');
                $xMax = (float) $wordNode->getAttribute('xMax');
                $yMax = (float) $wordNode->getAttribute('yMax');

                $words[] = new NativeWord(
                    text: $text,
                    left: $xMin,
                    top: $yMin,
                    width: max(0.0, $xMax - $xMin),
                    height: max(0.0, $yMax - $yMin),
                    page: $pageNumber,
                    metadata: [
                        'source' => 'poppler_pdftotext_bbox_layout',
                        'artifact_path' => $artifactPath,
                        'pdf_page_width' => $pageWidth,
                        'pdf_page_height' => $pageHeight,
                    ],
                );
            }

            $pages[$pageNumber] = $words;
        }

        return $pages;
    }
}