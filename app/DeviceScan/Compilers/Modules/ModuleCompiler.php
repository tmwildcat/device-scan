<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules;

use App\DeviceScan\Compilers\Modules\DTO\ModuleDetectedSectionDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleElectricalModelDto;
use App\DeviceScan\Compilers\Modules\Extraction\ModuleStcElectricalExtractor;
use App\DeviceScan\Compilers\Modules\Extraction\ModuleSupplementalExtractor;
use App\DeviceScan\Compilers\Modules\Validation\ModuleValidator;
use App\DeviceScan\Processing\Analysis\PageAnalysisProcessor;
use App\DeviceScan\Processing\Document\SourceDocument;
use App\DeviceScan\Processing\PageRenderer;
use App\DeviceScan\Processing\PdfLoader;
use App\DeviceScan\Processing\Sections\DetectedSection;
use App\DeviceScan\Processing\TextExtractor;

final class ModuleCompiler
{
    public function __construct(
        private readonly PdfLoader $pdfLoader,
        private readonly TextExtractor $textExtractor,
        private readonly PageRenderer $pageRenderer,
        private readonly PageAnalysisProcessor $pageAnalysisProcessor,
        private readonly ModuleSectionDetector $sectionDetector,
        private readonly ModuleStcElectricalExtractor $stcElectricalExtractor,
        private readonly ModuleSupplementalExtractor $supplementalExtractor,
        private readonly ModuleValidator $validator,
    ) {}

    public function compile(string $pdfPath): ModuleDto
    {
        $document = $this->pdfLoader->load($pdfPath);
        $document = $this->textExtractor->extract($document);
        $document = $this->pageRenderer->render($document);
        $document = $this->pageAnalysisProcessor->process($document);
        $document = $this->sectionDetector->detect($document);

        $stcResult = $this->stcElectricalExtractor->extract($document);
        $electricalStc = $stcResult->dto;
        $supplementalResult = $this->supplementalExtractor->extract($document);
        $extractionWarnings = array_values(array_unique([
            ...$stcResult->warnings,
            ...$supplementalResult->warnings,
        ]));

        $dto = new ModuleDto(
            manufacturer: $this->inferManufacturer($document),
            series: $this->inferSeries($document),
            family: $this->inferFamily($document),
            technology: $this->inferTechnology($document),
            models: $this->modelSeriesList($electricalStc?->models ?? []),
            electricalStc: $electricalStc,
            electricalVariants: [],
            mechanical: $supplementalResult->mechanical,
            operatingConditions: $supplementalResult->operatingConditions,
            temperatureCharacteristics: $supplementalResult->temperatureCharacteristics,
            warranty: $supplementalResult->warranty,
            packaging: $supplementalResult->packaging,
            certifications: $supplementalResult->certifications,
            sections: $this->sections($document),
            sourceMetadata: [
                'filename' => $document->filename,
                'path' => $document->metadata['path'] ?? null,
                'page_count' => $document->pageCount,
                'compiler' => self::class,
            ],
            extractionWarnings: $extractionWarnings,
            warnings: array_values(array_unique([
                ...$document->warnings,
                ...$extractionWarnings,
            ])),
        );

        $validation = $this->validator->validate($dto);

        return new ModuleDto(
            manufacturer: $dto->manufacturer,
            series: $dto->series,
            family: $dto->family,
            technology: $dto->technology,
            models: $dto->models,
            electricalStc: $dto->electricalStc,
            electricalVariants: $dto->electricalVariants,
            mechanical: $dto->mechanical,
            operatingConditions: $dto->operatingConditions,
            temperatureCharacteristics: $dto->temperatureCharacteristics,
            warranty: $dto->warranty,
            packaging: $dto->packaging,
            certifications: $dto->certifications,
            validation: $validation,
            sections: $dto->sections,
            sourceMetadata: $dto->sourceMetadata,
            extractionWarnings: $dto->extractionWarnings,
            warnings: $dto->warnings,
        );
    }

    /**
     * @param ModuleElectricalModelDto[] $models
     * @return string[]
     */
    private function modelSeriesList(array $models): array
    {
        return array_values(array_unique(array_map(
            fn (ModuleElectricalModelDto $model) => $model->modelSeries,
            array_filter($models, fn (ModuleElectricalModelDto $model) => $model->modelSeries !== null),
        )));
    }

    /**
     * @return ModuleDetectedSectionDto[]
     */
    private function sections(SourceDocument $document): array
    {
        $sections = [];

        foreach ($document->pages as $page) {
            foreach ($page->sections as $section) {
                if (! $section instanceof DetectedSection) {
                    continue;
                }

                $sections[] = new ModuleDetectedSectionDto(
                    type: $section->type,
                    title: $section->title,
                    page: $section->page,
                    startLine: $section->startLine,
                    endLine: $section->endLine,
                    metadata: $section->metadata,
                );
            }
        }

        return $sections;
    }

    private function inferManufacturer(SourceDocument $document): ?string
    {
        $filename = pathinfo($document->filename, PATHINFO_FILENAME);
        $firstToken = preg_split('/[-_]/', $filename)[0] ?? null;

        if (! is_string($firstToken) || trim($firstToken) === '') {
            return null;
        }

        return trim(preg_replace('/(?<!^)([A-Z])/', ' $1', $firstToken) ?? $firstToken);
    }

    private function inferSeries(SourceDocument $document): ?string
    {
        $filename = pathinfo($document->filename, PATHINFO_FILENAME);

        foreach ([
            'TigerNeo' => 'Tiger Neo',
            'HiMO-X6' => 'Hi-MO X6',
            'VertexN' => 'Vertex N',
            'HYPERSOL-G12' => 'HYPERSOL G12',
            'HYPERSOL-M10R' => 'HYPERSOL M10R',
            'ELAN-SHINE' => 'ELAN SHINE',
            'TOPBiHiKu6' => 'TOPBiHiKu6',
            'Series7' => 'Series 7',
            'Silk-Nova' => 'Silk Nova',
            'Performance6' => 'Performance 6',
            'NPeak3' => 'N-Peak 3',
            'ASTRO-N7' => 'ASTRO N7',
        ] as $needle => $series) {
            if (str_contains($filename, $needle)) {
                return $series;
            }
        }

        return null;
    }

    private function inferFamily(SourceDocument $document): ?string
    {
        $filename = pathinfo($document->filename, PATHINFO_FILENAME);

        if (preg_match('/\b([A-Z]{2,}\d+[A-Z0-9.\-()\/]+)\b/u', $filename, $match)) {
            return $match[1];
        }

        return null;
    }

    private function inferTechnology(SourceDocument $document): ?string
    {
        $filename = mb_strtolower(pathinfo($document->filename, PATHINFO_FILENAME));
        $text = mb_strtolower(implode(' ', array_map(
            fn ($page) => $page->text?->content ?? '',
            $document->pages,
        )));

        if (str_contains($filename, 'firstsolar') || str_contains($text, 'thin film') || str_contains($text, 'cadmium telluride')) {
            return 'thin-film';
        }

        if (str_contains($text, 'topcon')) {
            return 'n-type TOPCon';
        }

        if (str_contains($text, 'n-type') || str_contains($text, 'n type')) {
            return 'n-type mono c-Si';
        }

        if (str_contains($text, 'mono')) {
            return 'mono c-Si';
        }

        return null;
    }
}
