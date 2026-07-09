<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\Extraction;

use App\DeviceScan\Compilers\Modules\DTO\ModuleElectricalModelDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleElectricalStcDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleSourceValueDto;
use App\DeviceScan\Compilers\Modules\ModuleSectionDetector;
use App\DeviceScan\Processing\Document\Page;
use App\DeviceScan\Processing\Document\SourceDocument;
use App\DeviceScan\Processing\Sections\DetectedSection;
use App\DeviceScan\Processing\Tables\DTO\TableGrid;
use App\DeviceScan\Processing\Tables\Engineering\Common\EngineeringTable;
use App\DeviceScan\Processing\Tables\Engineering\Module\Electrical\ModuleElectricalGridMerger;
use App\DeviceScan\Processing\Tables\Engineering\Module\Electrical\ModuleElectricalTableInterpreter;
use Symfony\Component\Process\Process;

final class ModuleStcElectricalExtractor
{
    private const FIELD_MAP = [
        'rated_max_power' => ['property' => 'ratedMaxPowerW', 'unit' => 'W'],
        'open_circuit_voltage' => ['property' => 'openCircuitVoltageV', 'unit' => 'V'],
        'maximum_power_voltage' => ['property' => 'maximumPowerVoltageV', 'unit' => 'V'],
        'short_circuit_current' => ['property' => 'shortCircuitCurrentA', 'unit' => 'A'],
        'maximum_power_current' => ['property' => 'maximumPowerCurrentA', 'unit' => 'A'],
        'module_efficiency' => ['property' => 'moduleEfficiencyPercent', 'unit' => '%'],
    ];

    public function __construct(
        private readonly ModuleElectricalGridMerger $gridMerger,
        private readonly ModuleElectricalTableInterpreter $tableInterpreter,
    ) {}

    public function extract(SourceDocument $document): ModuleStcExtractionResult
    {
        $warnings = [];

        $fromLayoutText = $this->extractFromPopplerLayoutText($document);

        if ($fromLayoutText !== null && $fromLayoutText->models !== []) {
            $warnings[] = 'fallback_text_extraction_used';
            $warnings[] = 'module_stc_extracted_from_poppler_layout_text';
            $warnings = [
                ...$warnings,
                ...$this->warningsForStcDto($fromLayoutText),
            ];

            return new ModuleStcExtractionResult($fromLayoutText, $warnings);
        }

        $fromGrids = $this->extractFromGrids($document);

        if ($fromGrids !== null && $fromGrids->models !== []) {
            return new ModuleStcExtractionResult($fromGrids, $this->warningsForStcDto($fromGrids));
        }

        $fromText = $this->extractFromTextSections($document);

        if ($fromText !== null && $fromText->models !== []) {
            $warnings[] = 'fallback_text_extraction_used';
            $warnings[] = 'module_stc_extracted_from_text_fallback';
            $warnings = [
                ...$warnings,
                ...$this->warningsForStcDto($fromText),
            ];

            return new ModuleStcExtractionResult($fromText, $warnings);
        }

        return new ModuleStcExtractionResult(
            dto: null,
            warnings: ['stc_section_not_found', 'missing_electrical_stc'],
        );
    }

    private function extractFromPopplerLayoutText(SourceDocument $document): ?ModuleElectricalStcDto
    {
        $path = $document->metadata['path'] ?? null;

        if (! is_string($path) || ! is_file($path)) {
            return null;
        }

        $modelsByName = [];
        $ignoredVariants = false;
        $ignoredNoct = false;
        $foundStcCandidate = false;

        foreach ($document->pages as $page) {
            $text = $this->popplerLayoutTextForPage($path, $page->number);

            if ($text === null) {
                continue;
            }

            $slice = $this->stcTextSlice($text);

            if ($slice === null) {
                continue;
            }

            $foundStcCandidate = true;
            $ignoredVariants = $ignoredVariants || $slice['variant_ignored'];
            $ignoredNoct = $ignoredNoct || $slice['noct_ignored'];

            $section = new DetectedSection(
                type: ModuleSectionDetector::ELECTRICAL_STC,
                title: 'Poppler layout STC text slice',
                page: $page->number,
                startLine: 0,
                endLine: count($slice['lines']) - 1,
                lines: $slice['lines'],
                metadata: [
                    'source' => self::class,
                    'method' => 'poppler_layout_text',
                    'heading' => $slice['heading'],
                    'page_text' => $text,
                    'document_filename' => $document->filename,
                ],
            );

            foreach ($this->modelsFromTextSection($section) as $model) {
                $modelsByName[$this->modelKey($model)] = $model;
            }
        }

        if ($modelsByName === []) {
            return null;
        }

        return new ModuleElectricalStcDto(
            models: array_values($modelsByName),
            metadata: [
                'source' => self::class,
                'method' => 'poppler_layout_text',
                'stc_candidate_found' => $foundStcCandidate,
                'variant_section_ignored' => $ignoredVariants,
                'suspected_noct_or_bnpi_ignored' => $ignoredNoct,
            ],
        );
    }

    /**
     * @return array{lines:string[],heading:string,variant_ignored:bool,noct_ignored:bool}|null
     */
    private function stcTextSlice(string $text): ?array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        $start = null;
        $heading = '';

        foreach ($lines as $index => $line) {
            if (! $this->looksLikeStcHeading($line)) {
                continue;
            }

            $start = $index;
            $heading = trim($line);
            break;
        }

        if ($start === null) {
            return null;
        }

        $end = count($lines) - 1;
        $variantIgnored = false;
        $noctIgnored = false;

        for ($index = $start + 1; $index < count($lines); $index++) {
            $line = mb_strtolower($lines[$index]);

            if (
                str_contains($line, 'electrical data (noct')
                || str_contains($line, 'electrical data (nmot')
                || str_contains($line, 'electrical parameters | noct')
                || str_contains($line, 'electrical data - noct')
                || preg_match('/^\s*(?:noct|nmot)\s*:/iu', $lines[$index])
            ) {
                $end = $index - 1;
                $noctIgnored = true;
                break;
            }

            if (
                str_contains($line, 'electrical parameters | bnpi')
                || str_contains($line, 'bnpi:')
                || str_contains($line, 'bifacial gain')
                || str_contains($line, 'rear side power gain')
                || str_contains($line, 'electrical characteristics with')
            ) {
                $end = $index - 1;
                $variantIgnored = true;
                break;
            }

            if (
                $index > $start + 3
                && (
                    str_contains($line, 'temperature ratings')
                    || str_contains($line, 'temperature characteristics')
                    || str_contains($line, 'mechanical specifications')
                    || str_contains($line, 'packaging information')
                )
            ) {
                $end = $index - 1;
                break;
            }
        }

        $slice = array_values(array_filter(
            array_slice($lines, $start, max(1, $end - $start + 1)),
            fn (string $line) => trim($line) !== '',
        ));

        return $slice === []
            ? null
            : [
                'lines' => $slice,
                'heading' => $heading,
                'variant_ignored' => $variantIgnored,
                'noct_ignored' => $noctIgnored,
            ];
    }

    private function looksLikeStcHeading(string $line): bool
    {
        $normalized = mb_strtolower($line);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        foreach ([
            'electrical parameters | stc',
            'electrical data (stc',
            'electrical data - stc',
            'ratings at standard test conditions',
            'electrical data, front stc characteristics',
            'front stc characteristics',
            'electrical specifications',
            'electrical data product code',
            'electrical data - all data measured to stc',
            'all data measured to stc',
            'electrical performance',
            'electrical parameters at stc',
            'electrical characteristics',
        ] as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return $phrase !== 'electrical characteristics'
                    || str_contains($normalized, 'stc');
            }
        }

        return str_contains($normalized, 'electrical data')
            && str_contains($normalized, 'product code');
    }

    private function popplerLayoutTextForPage(string $path, int $page): ?string
    {
        $process = new Process([
            'pdftotext',
            '-layout',
            '-f',
            (string) $page,
            '-l',
            (string) $page,
            $path,
            '-',
        ]);

        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $output = trim($process->getOutput());

        return $output === '' ? null : $output;
    }

    private function extractFromGrids(SourceDocument $document): ?ModuleElectricalStcDto
    {
        $modelsByName = [];

        foreach ($document->pages as $page) {
            $grids = $page->ocr?->grids ?? [];

            if ($grids === [] || ! $this->pageHasStcContext($page)) {
                continue;
            }

            foreach ($this->gridMerger->merge($grids) as $grid) {
                if ($this->looksLikeVariantText($this->gridText($grid))) {
                    continue;
                }

                $table = $this->tableInterpreter->interpret($grid);

                if ($table === null) {
                    continue;
                }

                foreach ($this->modelsFromEngineeringTable($table, $page->number) as $model) {
                    $modelsByName[$this->modelKey($model)] = $model;
                }
            }
        }

        if ($modelsByName === []) {
            return null;
        }

        return new ModuleElectricalStcDto(
            models: array_values($modelsByName),
            metadata: [
                'source' => self::class,
                'method' => 'table_grid',
            ],
        );
    }

    /**
     * @return ModuleElectricalModelDto[]
     */
    private function modelsFromEngineeringTable(EngineeringTable $table, int $page): array
    {
        $models = [];

        foreach ($table->models as $modelSeries) {
            $modelSeries = trim((string) $modelSeries);

            if ($modelSeries === '') {
                continue;
            }

            $values = [];

            foreach (self::FIELD_MAP as $canonical => $field) {
                $raw = $this->engineeringTableValue($table, $canonical, $modelSeries);

                if ($raw === null) {
                    continue;
                }

                $values[$field['property']] = new ModuleSourceValueDto(
                    value: $this->toFloat($raw),
                    unit: $field['unit'],
                    sourceText: $raw,
                    sourcePage: $page,
                    sourceSection: ModuleSectionDetector::ELECTRICAL_STC,
                    confidence: 0.85,
                    metadata: [
                        'method' => 'table_grid',
                        'canonical_parameter' => $canonical,
                    ],
                );
            }

            if ($values === []) {
                continue;
            }

            $models[] = $this->makeModelDto($this->explicitModelIdentity($modelSeries, null), $values, ['method' => 'table_grid']);
        }

        return $models;
    }

    private function engineeringTableValue(EngineeringTable $table, string $parameter, string $model): ?string
    {
        foreach ($table->rows as $row) {
            if ($row->parameter !== $parameter) {
                continue;
            }

            $value = $row->values[$model] ?? null;

            return is_scalar($value) ? (string) $value : null;
        }

        return null;
    }

    private function extractFromTextSections(SourceDocument $document): ?ModuleElectricalStcDto
    {
        $modelsByName = [];

        foreach ($document->pages as $page) {
            foreach ($page->sections as $section) {
                if (! $section instanceof DetectedSection) {
                    continue;
                }

                if ($section->type !== ModuleSectionDetector::ELECTRICAL_STC) {
                    continue;
                }

                if ($this->looksLikeVariantText($section->content())) {
                    continue;
                }

                foreach ($this->modelsFromTextSection($section) as $model) {
                    $modelsByName[$this->modelKey($model)] = $model;
                }
            }
        }

        if ($modelsByName === []) {
            return null;
        }

        return new ModuleElectricalStcDto(
            models: array_values($modelsByName),
            metadata: [
                'source' => self::class,
                'method' => 'section_text',
            ],
        );
    }

    /**
     * @return ModuleElectricalModelDto[]
     */
    private function modelsFromTextSection(DetectedSection $section): array
    {
        $text = $section->content();
        $identityText = implode("\n", array_filter([
            $text,
            is_string($section->metadata['page_text'] ?? null) ? $section->metadata['page_text'] : null,
            is_string($section->metadata['document_filename'] ?? null) ? $section->metadata['document_filename'] : null,
        ]));

        $rowModels = $this->modelsFromDenseStcRows($section);

        if ($rowModels !== []) {
            return $rowModels;
        }

        $rows = $this->extractCanonicalRows($text);

        if (! isset($rows['rated_max_power'])) {
            return [];
        }

        $pairedStcNoct = $this->hasPairedStcNoctColumns($text);
        $pmaxValues = $this->selectStcValues($rows['rated_max_power'], $pairedStcNoct);
        $modelIdentities = $this->modelIdentities($identityText, count($pmaxValues), $pmaxValues);
        $modelCount = count($modelIdentities);

        $models = [];

        foreach ($modelIdentities as $index => $identity) {
            $values = [];

            foreach (self::FIELD_MAP as $canonical => $field) {
                $rowValues = $rows[$canonical] ?? [];
                $stcValues = $canonical === 'module_efficiency'
                    ? $rowValues
                    : $this->selectStcValues($rowValues, $pairedStcNoct, $modelCount);
                $sourceText = $stcValues[$index]['raw'] ?? null;

                if ($sourceText === null) {
                    continue;
                }

                $values[$field['property']] = new ModuleSourceValueDto(
                    value: $stcValues[$index]['value'] ?? null,
                    unit: $field['unit'],
                    sourceText: $sourceText,
                    sourcePage: $section->page,
                    sourceSection: $section->type,
                    confidence: 0.75,
                    metadata: [
                        'method' => 'section_text',
                        'canonical_parameter' => $canonical,
                        'section_title' => $section->title,
                    ],
                );
            }

            if ($values === []) {
                continue;
            }

            $models[] = $this->makeModelDto($identity, $values, ['method' => 'section_text']);
        }

        return $models;
    }

    private function extractCanonicalRows(string $text): array
    {
        $rows = [];
        $lines = preg_split('/\R/u', $text) ?: [];
        $expectedCount = null;

        foreach ($this->rowDefinitions() as $canonical => $patterns) {
            foreach ($lines as $lineIndex => $line) {
                if (! $this->lineMatchesAny($line, $patterns)) {
                    continue;
                }

                if ($this->shouldSkipCandidateRow($canonical, $line)) {
                    continue;
                }

                $values = $this->numericValuesAfterLabel($line, $patterns, $canonical);

                if ($canonical === 'rated_max_power' && count($values) < 3) {
                    $values = $this->nearbyPowerValuesBefore($lines, $lineIndex);
                }

                if ($expectedCount !== null && count($values) > 0 && count($values) < $expectedCount) {
                    $values = [
                        ...$values,
                        ...$this->continuationValues($lines, $lineIndex),
                    ];
                }

                $values = $this->filterValuesForCanonical($canonical, $values);

                if ($expectedCount !== null && count($values) > $expectedCount) {
                    $values = array_slice($values, 0, $expectedCount);
                }

                if ($values !== []) {
                    $rows[$canonical] = $values;

                    if ($canonical === 'rated_max_power') {
                        $expectedCount = count($values);
                    }

                    break;
                }
            }
        }

        return $rows;
    }

    /**
     * @return ModuleElectricalModelDto[]
     */
    private function modelsFromDenseStcRows(DetectedSection $section): array
    {
        $models = [];
        $modelFamily = $this->denseStcModelFamily($section->content());

        foreach ($section->lines as $line) {
            if (preg_match('/^\s*(\d{3})\s+(\d{3})\s*W\s+(\d+(?:\.\d+)?)\s*V\s+(\d+(?:\.\d+)?)\s*A\s+(\d+(?:\.\d+)?)\s*V\s+(\d+(?:\.\d+)?)\s*A\s+(\d+(?:\.\d+)?)\s*%/iu', $line, $match)) {
                $powerClass = (float) $match[2];
                $identity = $modelFamily !== null
                    ? $this->expandedFamilyIdentity($modelFamily, $powerClass)
                    : $this->wattClassIdentity($powerClass);

                $models[] = $this->makeModelDto($identity, [
                    'ratedMaxPowerW' => $this->stcSourceValue($match[2], 'W', $line, $section->page, 'rated_max_power', 0.78),
                    'maximumPowerVoltageV' => $this->stcSourceValue($match[3], 'V', $line, $section->page, 'maximum_power_voltage', 0.78),
                    'maximumPowerCurrentA' => $this->stcSourceValue($match[4], 'A', $line, $section->page, 'maximum_power_current', 0.78),
                    'openCircuitVoltageV' => $this->stcSourceValue($match[5], 'V', $line, $section->page, 'open_circuit_voltage', 0.78),
                    'shortCircuitCurrentA' => $this->stcSourceValue($match[6], 'A', $line, $section->page, 'short_circuit_current', 0.78),
                    'moduleEfficiencyPercent' => $this->stcSourceValue($match[7], '%', $line, $section->page, 'module_efficiency', 0.78),
                ], ['method' => 'dense_stc_row']);
            }
        }

        return $models;
    }

    private function denseStcModelFamily(string $text): ?string
    {
        if (preg_match('/\b(CS\d(?:\.\d)?-[A-Z0-9.\-]*xxx[A-Z0-9.\-]*)\b/iu', $text, $match) === 1) {
            return $match[1];
        }

        return null;
    }

    private function stcSourceValue(
        string $raw,
        string $unit,
        string $sourceText,
        int $page,
        string $canonical,
        float $confidence,
    ): ModuleSourceValueDto {
        return new ModuleSourceValueDto(
            value: (float) str_replace(',', '.', $raw),
            unit: $unit,
            sourceText: trim($sourceText),
            sourcePage: $page,
            sourceSection: ModuleSectionDetector::ELECTRICAL_STC,
            confidence: $confidence,
            metadata: [
                'method' => 'dense_stc_row',
                'canonical_parameter' => $canonical,
            ],
        );
    }

    private function continuationValues(array $lines, int $lineIndex): array
    {
        $values = [];

        for ($index = $lineIndex + 1; $index <= min(count($lines) - 1, $lineIndex + 2); $index++) {
            $line = $lines[$index] ?? '';

            if ($this->lineMatchesAny($line, array_merge(...array_values($this->rowDefinitions())))) {
                break;
            }

            preg_match_all('/[-+]?\d+(?:[.,]\d+)?\s*(?:Wp|W|V|A|%)?/iu', $line, $matches);

            foreach ($matches[0] ?? [] as $raw) {
                $raw = trim($raw);

                if (! preg_match('/[-+]?\d+(?:[.,]\d+)?/u', $raw, $number)) {
                    continue;
                }

                $values[] = [
                    'raw' => $raw,
                    'value' => (float) str_replace(',', '.', $number[0]),
                ];
            }

            if (count($values) >= 2) {
                break;
            }
        }

        return $values;
    }

    private function nearbyPowerValuesBefore(array $lines, int $lineIndex): array
    {
        for ($index = $lineIndex - 1; $index >= max(0, $lineIndex - 5); $index--) {
            preg_match_all('/(?<![.,])\b[3-7]\d{2}\b(?![.,])/u', $lines[$index] ?? '', $matches);

            $values = [];

            foreach ($matches[0] ?? [] as $raw) {
                $values[] = [
                    'raw' => $raw,
                    'value' => (float) $raw,
                ];
            }

            if (count($values) >= 3) {
                return $values;
            }
        }

        return [];
    }

    private function lineMatchesAny(string $line, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line) === 1) {
                return true;
            }
        }

        return false;
    }

    private function shouldSkipCandidateRow(string $canonical, string $line): bool
    {
        $normalized = mb_strtolower($line);

        if ($canonical === 'rated_max_power') {
            return str_contains($normalized, 'tolerance')
                || str_contains($normalized, 'uncertainty')
                || str_contains($normalized, 'degradation')
                || str_contains($normalized, 'temperature coefficient')
                || str_contains($normalized, 'coefficient');
        }

        if (in_array($canonical, [
            'open_circuit_voltage',
            'maximum_power_voltage',
            'short_circuit_current',
            'maximum_power_current',
        ], true)) {
            return preg_match('/^\s*(?:temperature|tc\s+of|coefficient)/u', $normalized) === 1;
        }

        return false;
    }

    private function numericValuesAfterLabel(string $line, array $patterns, string $canonical): array
    {
        $text = $line;

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line, $match, PREG_OFFSET_CAPTURE) !== 1) {
                continue;
            }

            $offset = (int) ($match[0][1] ?? 0);
            $length = strlen((string) ($match[0][0] ?? ''));
            $text = substr($line, $offset + $length);
            break;
        }

        $text = preg_replace('/\([^)]*%[^)]*\)/u', ' ', $text) ?? $text;
        $text = preg_replace('/\b(?:PMAX|PMPP|VMAX|VMPP|VMP|IMAX|IMPP|IMP|VOC|ISC)\b\s*(?:\([^)]+\))?/iu', ' ', $text) ?? $text;
        $text = preg_replace('/\bTK\s*\([^)]+\)/iu', ' ', $text) ?? $text;

        preg_match_all('/[-+]?\d+(?:[.,]\d+)?\s*(?:Wp|W|V|A|%)?/iu', $text, $matches);

        $values = [];

        foreach ($matches[0] ?? [] as $raw) {
            $raw = trim($raw);

            if (! preg_match('/[-+]?\d+(?:[.,]\d+)?/u', $raw, $number)) {
                continue;
            }

            $values[] = [
                'raw' => $raw,
                'value' => (float) str_replace(',', '.', $number[0]),
            ];
        }

        while (
            count($values) > 1
            && abs((float) $values[0]['value']) < 10
            && max(array_map(fn (array $value) => (float) $value['value'], $values)) >= 100
        ) {
            array_shift($values);
        }

        return $values;
    }

    private function filterValuesForCanonical(string $canonical, array $values): array
    {
        return array_values(array_filter($values, function (array $value) use ($canonical): bool {
            $number = (float) $value['value'];

            return match ($canonical) {
                'rated_max_power' => $number >= 100 && $number <= 800,
                'open_circuit_voltage', 'maximum_power_voltage' => $number >= 5 && $number <= 1000,
                'short_circuit_current', 'maximum_power_current' => $number > 0 && $number <= 100,
                'module_efficiency' => $number > 0 && $number <= 100,
                default => true,
            };
        }));
    }

    private function selectStcValues(array $values, bool $pairedStcNoct, ?int $modelCount = null): array
    {
        if (! $pairedStcNoct || count($values) <= 3) {
            return $values;
        }

        if ($modelCount !== null && count($values) === $modelCount) {
            return $values;
        }

        $selected = [];

        foreach ($values as $index => $value) {
            if ($index % 2 === 0) {
                $selected[] = $value;
            }
        }

        return $selected;
    }

    private function hasPairedStcNoctColumns(string $text): bool
    {
        return preg_match('/\bSTC\b\s+\b(?:NOCT|NMOT)\b/iu', $text) === 1
            || preg_match('/\bTesting Condition\b.*\bSTC\b.*\b(?:NOCT|NMOT)\b/isu', $text) === 1;
    }

    /**
     * @return array<int,array{model_series:?string,model_variants:string[],power_class_w:?float,display_name:string}>
     */
    private function modelIdentities(string $text, int $count, array $pmaxValues): array
    {
        $models = $this->extractModelNames($text);
        $powerClasses = array_map(
            fn (array $value) => (float) $value['value'],
            array_slice($pmaxValues, 0, $count),
        );
        $splitHeaderModels = $this->splitHeaderModelIdentities($text, $powerClasses);

        if ($splitHeaderModels !== [] && count($splitHeaderModels) === $count) {
            return $splitHeaderModels;
        }

        $publishedFamilyModels = $this->publishedFamilyModelIdentities($text, $powerClasses);

        if ($publishedFamilyModels !== [] && count($publishedFamilyModels) === $count) {
            return $publishedFamilyModels;
        }

        $grouped = $this->groupModelVariantsByPowerClass($models, $powerClasses);

        if (count($models) === $count) {
            return array_map(
                fn (int $index) => $this->explicitModelIdentity($models[$index], $powerClasses[$index] ?? null),
                array_keys($models),
            );
        }

        if ($grouped !== [] && count($grouped) === $count) {
            return $grouped;
        }

        if (count($models) === 1 && $count > 1) {
            if ($this->isPlaceholderModelFamily($models[0])) {
                return array_map(
                    fn (array $value) => $this->seriesPowerIdentity($models[0], (float) $value['value']),
                    array_slice($pmaxValues, 0, $count),
                );
            }

            $expanded = $this->expandModelFamily($models[0], $pmaxValues, $count);

            if (count($expanded) === $count) {
                return array_map(
                    fn (int $index) => $this->explicitModelIdentity(
                        $expanded[$index],
                        $powerClasses[$index] ?? null,
                        [$models[0], $expanded[$index]],
                    ),
                    array_keys($expanded),
                );
            }
        }

        return array_map(
            fn (array $value) => $this->wattClassIdentity((float) $value['value']),
            array_slice($pmaxValues, 0, $count),
        );
    }

    /**
     * @param string[] $models
     * @param float[] $powerClasses
     * @return array<int,array{model_series:?string,model_variants:string[],power_class_w:?float,display_name:string}>
     */
    private function groupModelVariantsByPowerClass(array $models, array $powerClasses): array
    {
        $grouped = [];

        foreach ($powerClasses as $powerClass) {
            $variants = array_values(array_filter(
                $models,
                fn (string $model) => $this->modelPowerClass($model) === (int) round($powerClass)
                    && ! $this->looksLikePowerRangeModel($model),
            ));

            if ($variants === []) {
                return [];
            }

            $grouped[] = $this->explicitModelIdentity($variants[0], $powerClass, $variants);
        }

        return $grouped;
    }

    private function modelPowerClass(string $model): ?int
    {
        if (preg_match('/(?<!\d)([3-7]\d{2})(?!\d)/u', $model, $match) !== 1) {
            return null;
        }

        return (int) $match[1];
    }

    private function looksLikePowerRangeModel(string $model): bool
    {
        return preg_match('/(?<!\d)[3-7]\d{2}\s*-\s*[3-7]\d{2}(?!\d)/u', $model) === 1;
    }

    /**
     * @param float[] $powerClasses
     * @return array<int,array{model_series:?string,model_variants:string[],power_class_w:?float,display_name:string}>
     */
    private function splitHeaderModelIdentities(string $text, array $powerClasses): array
    {
        if (preg_match('/\b(JAM\d+[A-Z0-9]*)\b/iu', $text, $baseMatch) !== 1) {
            return [];
        }

        preg_match_all('/-(\d{3}\/[A-Z0-9]+)\b/iu', $text, $suffixMatches);
        $suffixes = array_values(array_unique($suffixMatches[1] ?? []));

        $identities = [];

        foreach ($powerClasses as $powerClass) {
            $power = (string) ((int) round($powerClass));
            $suffix = null;

            foreach ($suffixes as $candidate) {
                if (str_starts_with($candidate, $power.'/')) {
                    $suffix = $candidate;
                    break;
                }
            }

            if ($suffix === null) {
                return [];
            }

            $modelSeries = $baseMatch[1].'-'.$suffix;
            $modelSeries = preg_replace('/-\d{3}\/([A-Z0-9]+)$/iu', '-$1', $modelSeries) ?? $modelSeries;
            $identities[] = $this->explicitModelIdentity(
                $modelSeries,
                $powerClass,
                [],
                $modelSeries.' '.$power.'W',
            );
        }

        return $identities;
    }

    /**
     * @param float[] $powerClasses
     * @return array<int,array{model_series:?string,model_variants:string[],power_class_w:?float,display_name:string}>
     */
    private function publishedFamilyModelIdentities(string $text, array $powerClasses): array
    {
        $family = null;
        $expandPowerClass = false;

        foreach ([
            '/\b(AB-G12R-\d+-XXX)\b/iu' => true,
            '/\b(AB-G12R-\d+)-\d{3}-\d{3}W?\b/iu' => true,
            '/\b(FS-7XXXA-TR1)\b/iu' => true,
            '/\b(PRODUCT:\s*)?(TSM-[A-Z0-9]+)\b/iu' => false,
            '/\b(CHSM\d+[A-Z]+\(?(?:DG)?\)?\/F-BH)\b/iu' => false,
            '/\b(CHSM\d+[A-Z]+-DG-F-BH)\b/iu' => false,
        ] as $pattern => $shouldExpand) {
            if (preg_match($pattern, $text, $match) !== 1) {
                continue;
            }

            $family = end($match);
            $expandPowerClass = $shouldExpand;
            break;
        }

        if (! is_string($family) || trim($family) === '') {
            return [];
        }

        $identities = [];

        foreach ($powerClasses as $powerClass) {
            $power = (string) ((int) round($powerClass));
            $modelSeries = $expandPowerClass
                ? $this->seriesFromPowerPlaceholder($family)
                : $family;

            $identities[] = $this->explicitModelIdentity(
                $modelSeries,
                $powerClass,
                [],
                $modelSeries.' '.$power.'W',
            );
        }

        return $identities;
    }

    private function seriesFromPowerPlaceholder(string $family): string
    {
        if (preg_match('/^(CS\d(?:\.\d)?-[A-Z0-9.\-]+)-XXX[A-Z0-9]*$/iu', $family, $match) === 1) {
            return $match[1];
        }

        if (preg_match('/^(.+)-XXX$/iu', $family, $match) === 1) {
            return $match[1];
        }

        return $family;
    }

    private function isPlaceholderModelFamily(string $model): bool
    {
        return preg_match('/(?:xxx|aaa)/iu', $model) === 1
            && ! $this->isExpandablePowerClassFamily($model);
    }

    private function isExpandablePowerClassFamily(string $model): bool
    {
        return preg_match('/^(?:CS\d(?:\.\d)?-[A-Z0-9.\-]*xxx[A-Z0-9.\-]*|FS-7XXXA-TR1|AB-G12R-\d+-XXX)$/iu', $model) === 1;
    }

    /**
     * @return array{model_series:?string,model_variants:string[],power_class_w:?float,display_name:string}
     */
    private function expandedFamilyIdentity(string $model, float $powerClassW): array
    {
        return $this->seriesPowerIdentity($model, $powerClassW);
    }

    /**
     * @return array{model_series:?string,model_variants:string[],power_class_w:?float,display_name:string}
     */
    private function seriesPowerIdentity(string $modelSeries, float $powerClassW): array
    {
        $modelSeries = $this->seriesFromPowerPlaceholder($modelSeries);

        return $this->explicitModelIdentity(
            $modelSeries,
            $powerClassW,
            [],
            $modelSeries.' '.((int) round($powerClassW)).'W',
        );
    }

    /**
     * @param string[]|null $variants
     * @return array{model_series:?string,model_variants:string[],power_class_w:?float,display_name:string}
     */
    private function explicitModelIdentity(string $modelSeries, ?float $powerClassW, ?array $variants = null, ?string $displayName = null): array
    {
        $variants ??= [];
        $variants = array_values(array_unique(array_filter($variants, fn (string $variant) => trim($variant) !== '')));

        return [
            'model_series' => $modelSeries,
            'model_variants' => $variants,
            'power_class_w' => $powerClassW,
            'display_name' => $displayName ?? $modelSeries,
        ];
    }

    /**
     * @return array{model_series:?string,model_variants:string[],power_class_w:?float,display_name:string}
     */
    private function wattClassIdentity(float $powerClassW): array
    {
        return [
            'model_series' => null,
            'model_variants' => [],
            'power_class_w' => $powerClassW,
            'display_name' => ((int) round($powerClassW)).'W',
        ];
    }

    /**
     * @return string[]
     */
    private function extractModelNames(string $text): array
    {
        preg_match_all(
            '/\b(?:JKM\d{3}[A-Z]?-?[A-Z0-9\-()\/]*|LR\d-\d+[A-Z]+-\d{3}M|JAM\d+[A-Z0-9\-\/]+|TSM-[A-Z0-9.\-]+|CS\d(?:\.\d)?-[A-Z0-9|.\-]+H|FS-\d{3,}[A-Z\-]*|FS-7XXXA-TR1|RECxxxNP3|REC\d+[A-Z0-9]+|VSMDH\.\d+\.[A-Z]+\.\d+|RSM\d+-\d+-\d{3}M|FU\d{3}MV(?:\s+Silk\s+Nova)?|SPR-P6-\d{3}-UPP|CHSM\d+[A-Z]+\(?(?:DG)?\)?\/F-BH|AB-G12R-\d+-\d{3})\b/iu',
            $text,
            $matches,
        );

        $models = [];

        foreach ($matches[0] ?? [] as $model) {
            $model = trim($model);

            if ($model !== '') {
                $models[$model] = true;
            }
        }

        return array_keys($models);
    }

    private function expandModelFamily(string $model, array $pmaxValues, int $count): array
    {
        $expanded = [];

        foreach (array_slice($pmaxValues, 0, $count) as $value) {
            $power = (string) ((int) round((float) $value['value']));
            $name = $model;

            if (preg_match('/xxx/iu', $name)) {
                $name = preg_replace('/xxx/iu', $power, $name, 1) ?? $name;
            } elseif (str_contains($name, 'AAA')) {
                $name = str_replace('AAA', $power, $name);
            } elseif (preg_match('/-\d{3}M$/u', $name)) {
                $name = preg_replace('/-\d{3}M$/u', '-'.$power.'M', $name) ?? $name;
            } else {
                $name .= '-'.$power;
            }

            $expanded[] = $name;
        }

        return $expanded;
    }

    /**
     * @param array{model_series:?string,model_variants:string[],power_class_w:?float,display_name:string} $identity
     */
    private function makeModelDto(array $identity, array $values, array $metadata): ModuleElectricalModelDto
    {
        return new ModuleElectricalModelDto(
            modelSeries: $identity['model_series'],
            modelVariants: $identity['model_variants'],
            powerClassW: $identity['power_class_w'],
            displayName: $identity['display_name'],
            ratedMaxPowerW: $values['ratedMaxPowerW'] ?? null,
            openCircuitVoltageV: $values['openCircuitVoltageV'] ?? null,
            maximumPowerVoltageV: $values['maximumPowerVoltageV'] ?? null,
            shortCircuitCurrentA: $values['shortCircuitCurrentA'] ?? null,
            maximumPowerCurrentA: $values['maximumPowerCurrentA'] ?? null,
            moduleEfficiencyPercent: $values['moduleEfficiencyPercent'] ?? null,
            metadata: $metadata,
        );
    }

    private function modelKey(ModuleElectricalModelDto $model): string
    {
        $power = $model->powerClassW !== null ? ((string) ((int) round($model->powerClassW))).'W' : null;

        return implode('|', array_filter([
            $model->modelSeries,
            $power,
            $model->displayName,
        ])) ?: spl_object_hash($model);
    }

    private function pageHasStcContext(Page $page): bool
    {
        foreach ($page->sections as $section) {
            if ($section instanceof DetectedSection && $section->type === ModuleSectionDetector::ELECTRICAL_STC) {
                return true;
            }
        }

        return str_contains(mb_strtolower($page->text?->content ?? ''), 'stc');
    }

    private function looksLikeVariantText(string $text): bool
    {
        $text = mb_strtolower($text);

        return str_contains($text, 'bifacial gain')
            || str_contains($text, 'rear side')
            || str_contains($text, 'irradiation ratio')
            || str_contains($text, 'bnpi');
    }

    private function gridText(TableGrid $grid): string
    {
        return trim(implode(' ', array_map(
            fn ($cell) => $cell->text,
            $grid->cells,
        )));
    }

    private function toFloat(string $raw): ?float
    {
        if (! preg_match('/[-+]?\d+(?:[.,]\d+)?/u', $raw, $match)) {
            return null;
        }

        return (float) str_replace(',', '.', $match[0]);
    }

    private function rowDefinitions(): array
    {
        return [
            'rated_max_power' => [
                '/\bRated\s+Maximum\s+Power\s*(?:\([^)]*\))?/iu',
                '/\bRated\s+Max\s+Power\s*(?:\([^)]*\))?/iu',
                '/\bRated\s+Power\s+in\s+Watts\s*[- ]?P?max\s*(?:\([^)]*\))?/iu',
                '/\bRated\s+output\s*(?:\([^)]*\))?/iu',
                '/\bMaximum\s+Power\b(?!\s+(?:Voltage|Current))(?:\s*[- ]?P?MAX|\s*\([^)]*\))?/iu',
                '/\bPeak\s+Power\s*,?\s*P?max\s*(?:\([^)]*\))?/iu',
                '/\bPeak\s+power\s*,?\s*P?max\s*(?:\([^)]*\))?/iu',
                '/\bNominal\s+Max\.?\s+Power\s*(?:\([^)]*\))?/iu',
                '/\bNominal\s+Power\s*(?:\([^)]*\))?/iu',
                '/\bModule\s+power\s*(?:\([^)]*\))?/iu',
                '/\bPower\s+Output\s*[- ]\s*P?MAX\s*(?:\([^)]*\))?/iu',
                '/\bPeak\s+Power\s+Watts\s*[- ]?P?MAX\s*(?:\([^)]*\))?/iu',
                '/\bPMAX\s*(?:\([^)]*\))?/iu',
                '/\bPmax\s*(?:\([^)]*\))?/u',
                '/\bPmpp\s*(?:\([^)]*\))?/iu',
            ],
            'open_circuit_voltage' => [
                '/\bOpen[- ]?circuit\s+Voltage\s*(?:[- ]?VOC|\([^)]*\))?/iu',
                '/\bVOC\s*(?:\([^)]*\))?/iu',
            ],
            'maximum_power_voltage' => [
                '/\bMaximum\s+Power\s+Voltage\s*(?:[- ]?VMPP?|\([^)]*\))?/iu',
                '/\bMaximum\s+Voltage\s+VMPP?\s*(?:\([^)]*\))?/iu',
                '/\bNominal\s+Power\s+Voltage\s*[- ]\s*VMPP?\s*(?:\([^)]*\))?/iu',
                '/\bRated\s+Voltage\s*(?:\([^)]*\))?/iu',
                '/\bVoltage\s+at\s+Maximum\s+Power\s*(?:\([^)]*\))?/iu',
                '/\bVoltage\s+at\s+PMAX\s*(?:\([^)]*\))?/iu',
                '/\bVMAX\s*(?:\([^)]*\))?/iu',
                '/\bVMPP?\s*(?:\([^)]*\))?/iu',
            ],
            'short_circuit_current' => [
                '/\bShort[- ]?circuit\s+Current\s*(?:[- ]?ISC|\([^)]*\))?/iu',
                '/\bISC\s*(?:\([^)]*\))?/iu',
            ],
            'maximum_power_current' => [
                '/\bMaximum\s+Power\s+Current\s*(?:[- ]?IMPP?|\([^)]*\))?/iu',
                '/\bMaximum\s+Current\s+IMPP?\s*(?:\([^)]*\))?/iu',
                '/\bNominal\s+Power\s+Current\s*[- ]\s*IMPP?\s*(?:\([^)]*\))?/iu',
                '/\bRated\s+Current\s*(?:\([^)]*\))?/iu',
                '/\bCurrent\s+at\s+Maximum\s+Power\s*(?:\([^)]*\))?/iu',
                '/\bCurrent\s+at\s+PMAX\s*(?:\([^)]*\))?/iu',
                '/\bIMAX\s*(?:\([^)]*\))?/iu',
                '/\bIMPP?\s*(?:\([^)]*\))?/iu',
            ],
            'module_efficiency' => [
                '/\bModule\s+e\S*ciency\s*(?:STC)?\s*(?:\([^)]*\)|\[%\])?/iu',
                '/\bModule\s+E(?:ffi|ﬃ)ciency\s*(?:STC)?\s*(?:\([^)]*\)|\[%\])?/iu',
                '/\bPanel\s+Efficiency\s*(?:\([^)]*\))?/iu',
                '/\bEfficiency\s*(?:\([^)]*\))?/iu',
                '/\bη\s*m\s*\(\s*%\s*\)/iu',
            ],
        ];
    }

    private function warningsForStcDto(ModuleElectricalStcDto $dto): array
    {
        $warnings = [];

        if (($dto->metadata['variant_section_ignored'] ?? false) === true) {
            $warnings[] = 'variant_section_ignored';
        }

        if (($dto->metadata['suspected_noct_or_bnpi_ignored'] ?? false) === true) {
            $warnings[] = 'suspected_noct_or_bnpi_ignored';
        }

        foreach ($dto->models as $model) {
            foreach ([
                $model->ratedMaxPowerW,
                $model->openCircuitVoltageV,
                $model->maximumPowerVoltageV,
                $model->shortCircuitCurrentA,
                $model->maximumPowerCurrentA,
                $model->moduleEfficiencyPercent,
            ] as $value) {
                if ($value === null) {
                    $warnings[] = 'partial_stc_rows';
                    break 2;
                }
            }
        }

        if ($dto->models === []) {
            $warnings[] = 'model_headers_not_found';
        }

        return array_values(array_unique($warnings));
    }
}
