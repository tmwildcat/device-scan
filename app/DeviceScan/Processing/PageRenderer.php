<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing;

use App\DeviceScan\Processing\Document\Page;
use App\DeviceScan\Processing\Document\SourceDocument;
use Symfony\Component\Process\Process;

final class PageRenderer
{
    public function render(SourceDocument $document): SourceDocument
    {
        $path = $document->metadata['path'] ?? null;

        if (! is_string($path) || ! is_file($path)) {
            return $document;
        }

        $previewDir = storage_path('app/public/device-scan/previews');

        if (! is_dir($previewDir)) {
            mkdir($previewDir, 0755, true);
        }

        $pages = [];

        foreach ($document->pages as $page) {
            $pages[] = $this->renderPage(
                $page,
                $path,
                $previewDir,
            );
        }

        return new SourceDocument(
            filename: $document->filename,
            mimeType: $document->mimeType,
            pageCount: $document->pageCount,
            pages: $pages,
            metadata: $document->metadata,
            warnings: $document->warnings,
            artifacts: $document->artifacts,
        );
    }

    private function renderPage(
        Page $page,
        string $pdfPath,
        string $previewDir,
    ): Page {

        $hash = md5($pdfPath);

        $outputBase = sprintf(
            '%s/%s-page-%03d',
            $previewDir,
            $hash,
            $page->number,
        );

        $imagePath = $outputBase.'.png';

        if (! file_exists($imagePath)) {

            $process = new Process([
                'pdftoppm',
                '-png',
                '-singlefile',
                '-f',
                (string) $page->number,
                '-l',
                (string) $page->number,
                '-r',
                '180',
                $pdfPath,
                $outputBase,
            ]);

            $process->setTimeout(30);

            $process->run();

            if (! $process->isSuccessful()) {

                return $page;
            }
        }

       return new Page(
            number: $page->number,
            text: $page->text,
            imageUrl: '/storage/device-scan/previews/'.basename($imagePath),
            tables: $page->tables,
            images: $page->images,
            sections: $page->sections,
            ocr: $page->ocr,
            metadata: [
                ...$page->metadata,
                'rendered_image_path' => $imagePath,
            ],
        );
    }
}