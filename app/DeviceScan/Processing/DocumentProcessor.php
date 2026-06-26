<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing;

use App\DeviceScan\Datasheets\Datasheet;
use Illuminate\Http\UploadedFile;

final class DocumentProcessor
{
    public function __construct(
        private readonly PdfLoader $pdfLoader,
        private readonly TextExtractor $textExtractor,
        private readonly PageRenderer $pageRenderer,
        private readonly TableDetector $tableDetector,
        private readonly DatasheetAssembler $datasheetAssembler,
    ) {}

    public function process(UploadedFile|string $file, string $deviceType): Datasheet
    {
        return $this->processWithSource($file, $deviceType)['datasheet'];
    }

    public function processWithSource(UploadedFile|string $file, string $deviceType): array
    {
        $document = $this->pdfLoader->load($file);

        $document = $this->textExtractor->extract($document);

        $document = $this->pageRenderer->render($document);

        $document = $this->tableDetector->detect($document);

        return [
            'source_document' => $document,
            'datasheet' => $this->datasheetAssembler->assemble(
                document: $document,
                deviceType: $deviceType,
            ),
        ];
    }
}