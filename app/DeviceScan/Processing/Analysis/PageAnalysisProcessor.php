<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Analysis;

use App\DeviceScan\Processing\Document\Page;
use App\DeviceScan\Processing\Document\SourceDocument;
use App\DeviceScan\Processing\Native\Analysis\NativeGridDetector;
use App\DeviceScan\Processing\Native\Analysis\NativeTableRegionDetector;
use App\DeviceScan\Processing\Native\Analysis\NativeTextRunBuilder;
use App\DeviceScan\Processing\Ocr\EngineeringBlockClassifier;
use App\DeviceScan\Processing\Ocr\OcrBlockBuilder;
use App\DeviceScan\Processing\Ocr\OcrLayoutAnalyzer;
use App\DeviceScan\Processing\Ocr\OcrResult;
use App\DeviceScan\Processing\Ocr\PageOcrExtractor;
use App\DeviceScan\Processing\Tables\Canonical\CanonicalGridDebugAnnotator;
use App\DeviceScan\Processing\Tables\Fusion\HybridCellTextFusion;
use App\DeviceScan\Processing\Tables\Fusion\HybridTableRegionDetector;
use App\DeviceScan\Processing\Tables\Geometry\GridDetector;
use App\DeviceScan\Processing\Tables\Geometry\TableRegionDetector;

final class PageAnalysisProcessor
{
    public function __construct(
        private readonly PageOcrExtractor $ocrExtractor,
        private readonly OcrLayoutAnalyzer $layoutAnalyzer,
        private readonly OcrBlockBuilder $blockBuilder,
        private readonly EngineeringBlockClassifier $blockClassifier,
        private readonly TableRegionDetector $tableRegionDetector,
        private readonly HybridTableRegionDetector $hybridTableRegionDetector,
        private readonly NativeTextRunBuilder $nativeTextRunBuilder,
        private readonly NativeTableRegionDetector $nativeTableRegionDetector,
        private readonly NativeGridDetector $nativeGridDetector,
        private readonly GridDetector $gridDetector,
        private readonly HybridCellTextFusion $hybridCellTextFusion,
        private readonly CanonicalGridDebugAnnotator $canonicalGridDebugAnnotator,
    ) {}

    public function process(SourceDocument $document): SourceDocument
    {
        return new SourceDocument(
            filename: $document->filename,
            mimeType: $document->mimeType,
            pageCount: $document->pageCount,
            pages: array_map(
                fn (Page $page) => $this->analysePage($page),
                $document->pages,
            ),
            metadata: $document->metadata,
            warnings: $document->warnings,
            artifacts: $document->artifacts,
        );
    }

    private function analysePage(Page $page): Page
    {
        $ocr = $this->ocrExtractor->extract($page);

        if ($ocr !== null) {
            $lines = $this->layoutAnalyzer->lines($ocr);

            $blocks = array_map(
                fn ($block) => $this->blockClassifier->classify($block),
                $this->blockBuilder->build($lines),
            );

            $intermediateOcr = new OcrResult(
                page: $ocr->page,
                words: $ocr->words,
                lines: $lines,
                blocks: $blocks,
                tableRegions: [],
                grids: [],
                engineeringTables: [],
                metadata: $ocr->metadata,
            );

            $tableRegions = $this->tableRegionDetector->detect($intermediateOcr);

            $nativeRuns = [];
            $nativeWords = $page->text?->words ?? [];

            if ($nativeWords !== []) {
                $nativeRuns = $this->nativeTextRunBuilder->build($nativeWords);
                $nativeRegions = $this->nativeTableRegionDetector->detect($nativeRuns);

                if ($tableRegions === []) {
                    $tableRegions = $nativeRegions;
                }
            }

            if ($tableRegions === []) {
                $tableRegions = $this->hybridTableRegionDetector->detect(
                    page: $page,
                    ocr: $intermediateOcr,
                );
            }

            $grids = [];

            foreach ($tableRegions as $region) {
                $grid = $region->type === 'native_table'
                    ? $this->nativeGridDetector->detect($region, $nativeRuns)
                    : $this->gridDetector->detect($region);

                if ($grid !== null) {
                    $grids[] = $grid;
                }
            }

            $grids = array_map(
                fn ($grid) => $this->hybridCellTextFusion->fuse($grid, $page),
                $grids,
            );

            $grids = array_map(
                fn ($grid) => $this->canonicalGridDebugAnnotator->annotate($grid),
                $grids,
            );

            $ocr = new OcrResult(
                page: $ocr->page,
                words: $ocr->words,
                lines: $lines,
                blocks: $blocks,
                tableRegions: $tableRegions,
                grids: $grids,
                engineeringTables: [],
                metadata: [
                    ...$ocr->metadata,
                    'table_region_count' => count($tableRegions),
                    'grid_count' => count($grids),
                    'native_run_count' => count($nativeRuns),
                    'hybrid_cell_text_fusion' => true,
                    'native_table_region_detection' => true,
                ],
            );
        }

        return new Page(
            number: $page->number,
            text: $page->text,
            imageUrl: $page->imageUrl,
            tables: $page->tables,
            images: $page->images,
            sections: $page->sections,
            ocr: $ocr,
            metadata: $page->metadata,
        );
    }
}
