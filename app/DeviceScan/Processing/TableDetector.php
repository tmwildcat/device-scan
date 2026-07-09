<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing;

use App\DeviceScan\Processing\Document\Page;
use App\DeviceScan\Processing\Document\SourceDocument;
use App\DeviceScan\Processing\Tables\Modules\ModuleElectricalMatrixExtractor;
use App\DeviceScan\Processing\Tables\Modules\ModuleMechanicalTableExtractor;

final class TableDetector
{
    public function __construct(
        private readonly ModuleElectricalMatrixExtractor $moduleElectricalMatrixExtractor,
        private readonly ModuleMechanicalTableExtractor $moduleMechanicalTableExtractor,
    ) {}

    public function detect(SourceDocument $document, string $deviceType): SourceDocument
    {
        $pages = array_map(
            fn (Page $page) => $this->detectOnPage($page, $deviceType),
            $document->pages,
        );

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

    private function detectOnPage(Page $page, string $deviceType): Page
    {
        $tables = [];

        foreach ($page->sections as $section) {
            $mechanicalTable = $this->moduleMechanicalTableExtractor->extract($section);

            if ($mechanicalTable !== null) {
                $tables[] = $mechanicalTable;
            }
        }

        $moduleTable = $this->moduleElectricalMatrixExtractor->extract($page);

        if ($moduleTable !== null) {
            $tables[] = $moduleTable;
        }

        if ($tables === []) {
            return $page;
        }

        return new Page(
            number: $page->number,
            text: $page->text,
            imageUrl: $page->imageUrl,
            tables: $tables,
            images: $page->images,
            sections: $page->sections,
            ocr: $page->ocr,
            metadata: $page->metadata,
        );
    }
}
