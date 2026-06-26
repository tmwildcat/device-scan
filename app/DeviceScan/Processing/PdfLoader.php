<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing;

use App\DeviceScan\Processing\Document\Page;
use App\DeviceScan\Processing\Document\SourceDocument;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Smalot\PdfParser\Parser;
use Throwable;

final class PdfLoader
{
    public function load(UploadedFile|string $file): SourceDocument
    {
        $path = $file instanceof UploadedFile
            ? $file->getRealPath()
            : $file;

        if (! is_string($path) || ! is_file($path)) {
            throw new RuntimeException('Datasheet file could not be found.');
        }

        $filename = $file instanceof UploadedFile
            ? $file->getClientOriginalName()
            : basename($path);

        $mimeType = $file instanceof UploadedFile
            ? ($file->getMimeType() ?: 'application/pdf')
            : (mime_content_type($path) ?: 'application/pdf');

        $warnings = [];
        $pdf = null;
        $pageCount = null;

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($path);
            $pageCount = count($pdf->getPages());
        } catch (Throwable $e) {
            $warnings[] = 'PDF parsing failed during load: '.$e->getMessage();
        }

        return new SourceDocument(
            filename: $filename,
            mimeType: $mimeType,
            pageCount: $pageCount,
            pages: $this->makePages($pageCount),
            metadata: [
                'path' => $path,
                'size_bytes' => filesize($path) ?: null,
            ],
            warnings: $warnings,
            artifacts: [
                'pdf' => $pdf,
            ],
        );
    }

    /**
     * @return Page[]
     */
    private function makePages(?int $pageCount): array
    {
        if (! $pageCount || $pageCount < 1) {
            return [];
        }

        return array_map(
            fn (int $number) => new Page(number: $number),
            range(1, $pageCount),
        );
    }
}