<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\Extraction;

use App\DeviceScan\Compilers\Modules\DTO\ModuleMechanicalDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleOperatingConditionsDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleCertificationsDto;
use App\DeviceScan\Compilers\Modules\DTO\ModulePackagingDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleSourceValueDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleTemperatureCharacteristicsDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleWarrantyDto;
use App\DeviceScan\Processing\Document\SourceDocument;
use Symfony\Component\Process\Process;

final class ModuleSupplementalExtractor
{
    public function extract(SourceDocument $document): ModuleSupplementalExtractionResult
    {
        $pages = $this->popplerLayoutPages($document);
        $warnings = [];

        if ($pages === []) {
            return new ModuleSupplementalExtractionResult(
                mechanical: null,
                operatingConditions: null,
                temperatureCharacteristics: null,
                warranty: null,
                packaging: null,
                certifications: null,
                warnings: ['module_supplemental_poppler_layout_text_unavailable'],
            );
        }

        $mechanical = $this->mechanical($pages);
        $operating = $this->operating($pages);
        $temperature = $this->temperature($pages);
        $warranty = $this->warranty($pages);
        $packaging = $this->packaging($pages);
        $certifications = $this->certifications($pages);

        if ($mechanical === null) {
            $warnings[] = 'missing_module_mechanical';
        }

        if ($operating === null) {
            $warnings[] = 'missing_module_operating_conditions';
        }

        if ($temperature === null) {
            $warnings[] = 'missing_module_temperature_characteristics';
        }

        if ($warranty === null) {
            $warnings[] = 'missing_module_warranty';
        }

        return new ModuleSupplementalExtractionResult(
            mechanical: $mechanical,
            operatingConditions: $operating,
            temperatureCharacteristics: $temperature,
            warranty: $warranty,
            packaging: $packaging,
            certifications: $certifications,
            warnings: $warnings,
        );
    }

    /**
     * @return array<int,string>
     */
    private function popplerLayoutPages(SourceDocument $document): array
    {
        $path = $document->metadata['path'] ?? null;

        if (! is_string($path) || ! is_file($path)) {
            return [];
        }

        $process = new Process(['pdftotext', '-layout', $path, '-']);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            return [];
        }

        $pages = [];

        foreach (preg_split("/\f/u", $process->getOutput()) ?: [] as $index => $text) {
            $text = trim($text);

            if ($text !== '') {
                $pages[$index + 1] = $text;
            }
        }

        return $pages;
    }

    private function mechanical(array $pages): ?ModuleMechanicalDto
    {
        $dimensions = $this->dimensionValue($pages);
        $dimensionParts = $dimensions !== null ? $this->dimensionParts((string) $dimensions->value) : [];
        $junctionBox = $this->value($pages, 'junction_box', [
            '/\bJunction\s+Box\b/iu',
            '/\bJ-Box\b/iu',
        ]);

        $dto = new ModuleMechanicalDto(
            dimensions: $dimensions,
            lengthMm: $this->dimensionPartValue($dimensionParts, 0, $dimensions, 'length_mm'),
            widthMm: $this->dimensionPartValue($dimensionParts, 1, $dimensions, 'width_mm'),
            thicknessMm: $this->dimensionPartValue($dimensionParts, 2, $dimensions, 'thickness_mm'),
            weightKg: $this->numericValue($pages, 'weight_kg', [
                '/\bWeight\b/iu',
            ], 'kg'),
            cellType: $this->value($pages, 'cell_type', [
                '/\bCell\s+Type\b/iu',
                '/\bSolar\s+Cells\b/iu',
            ]),
            cellCount: $this->numericValue($pages, 'cell_count', [
                '/\bNo\.\s*of\s+cells\b/iu',
                '/\bCell\s+Orientation\b/iu',
            ]),
            junctionBox: $junctionBox,
            connector: $this->value($pages, 'connector', [
                '/\bConnector\b/iu',
            ]),
            cableLength: $this->value($pages, 'cable_length', [
                '/\bCable\s+Length\b/iu',
                '/\bOutput\s+Cables?\b/iu',
                '/\bCables\b/iu',
            ]),
            glass: $this->value($pages, 'glass', [
                '/\bFront\s+Glass\/Back\s+Glass\b/iu',
                '/\bFront\s+Glass\b/iu',
                '/\bGlass\s{2,}/iu',
            ]),
            frame: $this->frameValue($pages),
            bypassDiodes: $this->bypassDiodes($junctionBox),
            packaging: $this->packagingValue($pages),
            metadata: ['method' => 'poppler_layout_text'],
        );

        return $this->hasAnyValue($dto->toArray()) ? $dto : null;
    }

    private function operating(array $pages): ?ModuleOperatingConditionsDto
    {
        $dto = new ModuleOperatingConditionsDto(
            maximumSystemVoltage: $this->numericValue($pages, 'maximum_system_voltage', [
                '/\bMaximum\s+System\s+Voltage\b/iu',
                '/\bMax\.?\s+System\s+Voltage\b/iu',
            ], 'V'),
            operatingTemperature: $this->temperatureRangeValue($pages, 'operating_temperature', [
                '/\bOperating\s+Temperature(?:\([^)]+\))?\b/iu',
                '/\bOperational\s+Temperature\b/iu',
            ]),
            maximumSeriesFuseRating: $this->numericValue($pages, 'maximum_series_fuse_rating', [
                '/\bMaximum\s+Series\s+Fuse\s+Rating\b/iu',
                '/\bMax\s+Series\s+Fuse\s+Rating\b/iu',
            ], 'A'),
            staticLoadFront: $this->numericValue($pages, 'static_load_front', [
                '/\bMaximum\s+Static\s+Load,\s*Front\*?\b/iu',
                '/\bFront\s+Side\s+Maximum\s+Static\s+Loading\b/iu',
            ], 'Pa'),
            staticLoadBack: $this->numericValue($pages, 'static_load_back', [
                '/\bMaximum\s+Static\s+Load,\s*Back\*?\b/iu',
                '/\bRear\s+Side\s+Maximum\s+Static\s+Loading\b/iu',
            ], 'Pa'),
            safetyClass: $this->classValue($pages, 'safety_class', [
                '/\bSafety\s+Class\b/iu',
                '/\bProtection\s+Class\b/iu',
            ]),
            fireRating: $this->classValue($pages, 'fire_rating', [
                '/\bFire\s+Performance\b/iu',
                '/\bFire\s+Rating\b/iu',
            ]),
            bifaciality: $this->numericValue($pages, 'bifaciality', [
                '/\bBifaciality\b/iu',
                '/\bPower\s+Bifaciality\b/iu',
            ], '%'),
            metadata: ['method' => 'poppler_layout_text'],
        );

        return $this->hasAnyValue($dto->toArray()) ? $dto : null;
    }

    private function temperature(array $pages): ?ModuleTemperatureCharacteristicsDto
    {
        $dto = new ModuleTemperatureCharacteristicsDto(
            nominalOperatingCellTemperature: $this->numericValue($pages, 'nominal_operating_cell_temperature', [
                '/\bNominal\s+Operating\s+Cell\s+Temperature\s*\(?(?:NOCT|NMOT)?\)?\b/iu',
                '/\bNOCT\s*\(\s*Nominal\s+Operating\s+Cell\s+Temperature\s*\)\b/iu',
            ], '°C'),
            temperatureCoefficientPmax: $this->temperatureCoefficient($pages, 'temperature_coefficient_pmax', [
                '/\bTemperature\s+Coe(?:ffi|ﬃ)cients?\s+of\s+P(?:max|MAX|mp|mp)\b/iu',
                '/\bTemperature\s+Coe(?:ffi|ﬃ)cient\s+of\s+Pmax\b/iu',
                '/\bMAX\b/iu',
            ]),
            temperatureCoefficientVoc: $this->temperatureCoefficient($pages, 'temperature_coefficient_voc', [
                '/\bTemperature\s+Coe(?:ffi|ﬃ)cients?\s+of\s+Voc\b/iu',
                '/\bTemperature\s+Coe(?:ffi|ﬃ)cient\s+of\s+Voc\b/iu',
                '/\bOC\b/iu',
            ]),
            temperatureCoefficientIsc: $this->temperatureCoefficient($pages, 'temperature_coefficient_isc', [
                '/\bTemperature\s+Coe(?:ffi|ﬃ)cients?\s+of\s+Isc\b/iu',
                '/\bTemperature\s+Coe(?:ffi|ﬃ)cient\s+of\s+Isc\b/iu',
                '/\bSC\b/iu',
            ]),
            metadata: ['method' => 'poppler_layout_text'],
        );

        return $this->hasAnyValue($dto->toArray()) ? $dto : null;
    }

    private function warranty(array $pages): ?ModuleWarrantyDto
    {
        $dto = new ModuleWarrantyDto(
            productWarrantyYears: $this->warrantyYears($pages, 'product_warranty_years', [
                '/\b(\d{1,2})\s*[- ]?\s*year\s+product\b/iu',
                '/\bproduct\s+warranty\b/iu',
                '/\bWarranty\s+for\s+Materials\s+and\s+Processing\b/iu',
            ]),
            linearPowerWarrantyYears: $this->warrantyYears($pages, 'linear_power_warranty_years', [
                '/\b(\d{1,2})\s*[- ]?\s*year\s+(?:linear\s+)?power\b/iu',
                '/\bLinear\s+Power\s+Warranty\b/iu',
                '/\bLinear\s+Performance\s+Warranty\b/iu',
            ]),
            firstYearDegradationPercent: $this->percentValue($pages, 'first_year_degradation_percent', [
                '/\b1(?:st)?[- ]?year\s+Degradation\b/iu',
                '/\bFIRST\s+YEAR\s+POWER\s+DEGRADATION\b/iu',
                '/\b1st\s+year\s+power\s+degradation\b/iu',
            ]),
            annualDegradationPercent: $this->percentValue($pages, 'annual_degradation_percent', [
                '/\bAnnual\s+Degradation\b/iu',
                '/\bYEAR\s+2-25\s+POWER\s+DEGRADATION\b/iu',
                '/\bAnnual\s+Power\s+Attenuation\b/iu',
            ]),
            endOfWarrantyOutputPercent: $this->endOfWarrantyOutput($pages),
            metadata: ['method' => 'poppler_layout_text'],
        );

        return $this->hasAnyValue($dto->toArray()) ? $dto : null;
    }

    private function packaging(array $pages): ?ModulePackagingDto
    {
        $raw = $this->packagingValue($pages);
        $modulesPerPallet = null;
        $modulesPerContainer = null;
        $palletsPerContainer = null;

        foreach ($pages as $page => $text) {
            foreach (preg_split('/\R/u', $text) ?: [] as $line) {
                $normalized = mb_strtolower($line);

                if ($modulesPerPallet === null && preg_match('/\b(\d{1,3})\s*(?:pcs|pieces|modules)?\s*\/\s*pallets?\b/iu', $line, $match)) {
                    $modulesPerPallet = $this->sourceValue(
                        value: (int) $match[1],
                        field: 'modules_per_pallet',
                        sourceText: trim($line),
                        sourcePage: (int) $page,
                        unit: 'modules',
                    );
                }

                if ($modulesPerPallet === null && str_contains($normalized, 'modules per pallet') && preg_match('/\b(\d{1,3})\b/u', $line, $match)) {
                    $modulesPerPallet = $this->sourceValue(
                        value: (int) $match[1],
                        field: 'modules_per_pallet',
                        sourceText: trim($line),
                        sourcePage: (int) $page,
                        unit: 'modules',
                    );
                }

                if ($modulesPerContainer === null && preg_match('/\b(\d{2,4})\s*(?:pcs|pieces|modules)?\s*\/\s*(?:40[’\']?HQ\s*)?container\b/iu', $line, $match)) {
                    $modulesPerContainer = $this->sourceValue(
                        value: (int) $match[1],
                        field: 'modules_per_container',
                        sourceText: trim($line),
                        sourcePage: (int) $page,
                        unit: 'modules',
                    );
                }

                if ($modulesPerContainer === null && preg_match('/modules\s+per\s+40[’\']?\s*container\s*:\s*(\d{2,4})/iu', $line, $match)) {
                    $modulesPerContainer = $this->sourceValue(
                        value: (int) $match[1],
                        field: 'modules_per_container',
                        sourceText: trim($line),
                        sourcePage: (int) $page,
                        unit: 'modules',
                    );
                }

                if ($palletsPerContainer === null && preg_match('/\b(\d{1,3})\s*pallets?\s*\/\s*(?:40[’\']?HQ\s*)?container\b/iu', $line, $match)) {
                    $palletsPerContainer = $this->sourceValue(
                        value: (int) $match[1],
                        field: 'pallets_per_container',
                        sourceText: trim($line),
                        sourcePage: (int) $page,
                        unit: 'pallets',
                    );
                }
            }
        }

        $dto = new ModulePackagingDto(
            modulesPerPallet: $modulesPerPallet,
            modulesPerContainer: $modulesPerContainer,
            palletsPerContainer: $palletsPerContainer,
            rawPackaging: $raw,
            metadata: ['method' => 'poppler_layout_text'],
        );

        return $this->hasAnyValue($dto->toArray()) ? $dto : null;
    }

    private function certifications(array $pages): ?ModuleCertificationsDto
    {
        $patterns = [
            'IEC 61215' => '/\bIEC\s*61215\b/iu',
            'IEC 61730' => '/\bIEC\s*61730\b/iu',
            'UL 61730' => '/\bUL\s*61730\b/iu',
            'ISO 9001' => '/\bISO\s*9001(?::\d{4})?\b/iu',
            'ISO 14001' => '/\bISO\s*14001(?::\d{4})?\b/iu',
            'ISO 45001' => '/\bISO\s*45001(?::\d{4})?\b/iu',
        ];
        $items = [];

        foreach ($pages as $page => $text) {
            foreach (preg_split('/\R/u', $text) ?: [] as $line) {
                foreach ($patterns as $label => $pattern) {
                    if (isset($items[$label]) || preg_match($pattern, $line) !== 1) {
                        continue;
                    }

                    $items[$label] = $this->sourceValue(
                        value: $label,
                        field: 'certification',
                        sourceText: trim($line),
                        sourcePage: (int) $page,
                        confidence: 0.8,
                        normalizedValue: $label,
                    );
                }
            }
        }

        if ($items === []) {
            return null;
        }

        return new ModuleCertificationsDto(
            items: array_values($items),
            metadata: ['method' => 'poppler_layout_text'],
        );
    }

    private function value(array $pages, string $field, array $labels): ?ModuleSourceValueDto
    {
        $match = $this->findLabeledValue($pages, $labels);

        if ($match === null) {
            return null;
        }

        return $this->sourceValue(
            value: $this->cleanValue($match['value']),
            field: $field,
            sourceText: $match['line'],
            sourcePage: $match['page'],
        );
    }

    private function classValue(array $pages, string $field, array $labels): ?ModuleSourceValueDto
    {
        $match = $this->findLabeledValue($pages, $labels);

        if ($match === null) {
            return null;
        }

        $value = $this->cleanValue($match['value']);

        if (preg_match('/\bClass\s+[A-Z0-9ⅡIVX]+|\bIEC\s+Class\s+[A-Z]|\bUL\s+Type\s+\d+(?:\/Class\s+[A-Z])?/iu', $value, $class)) {
            $value = $class[0];
        }

        return $this->sourceValue(
            value: $value,
            field: $field,
            sourceText: $match['line'],
            sourcePage: $match['page'],
        );
    }

    private function packagingValue(array $pages): ?ModuleSourceValueDto
    {
        foreach ($pages as $page => $text) {
            foreach (preg_split('/\R/u', $text) ?: [] as $line) {
                if (! preg_match("/\b(?:\d+\s*pcs|modules\s+per|pieces|pallet|container|40[’']?HQ)\b/iu", $line)) {
                    continue;
                }

                if (! preg_match("/(?:\d+\s*(?:pcs|pieces)?[^\n]*?(?:pallet|container|40[’']?HQ)|Modules\s+per\s+box\s*:\s*\d+\s*pieces|Modules\s+per\s+40[’']?\s*container\s*:\s*\d+\s*pieces)/iu", $line, $match)) {
                    continue;
                }

                return $this->sourceValue(
                    value: $this->cleanValue($match[0]),
                    field: 'packaging',
                    sourceText: trim($line),
                    sourcePage: (int) $page,
                );
            }
        }

        return null;
    }

    private function frameValue(array $pages): ?ModuleSourceValueDto
    {
        foreach ($this->findLabeledValues($pages, ['/\bFrame\s{2,}/iu']) as $match) {
            $value = $this->cleanValue($match['value']);

            if ($value === '' || mb_strtolower($value) === 'frame') {
                continue;
            }

            if (! preg_match('/alum|alloy|steel|anodized|mm/iu', $value)) {
                continue;
            }

            return $this->sourceValue(
                value: $value,
                field: 'frame',
                sourceText: $match['line'],
                sourcePage: $match['page'],
            );
        }

        return null;
    }

    private function numericValue(array $pages, string $field, array $labels, ?string $unit = null): ?ModuleSourceValueDto
    {
        foreach ($this->findLabeledValues($pages, $labels) as $match) {
            $valueText = $match['value'];
            $sourceValue = null;

            if ($field === 'maximum_system_voltage' && preg_match('/\b(1000|1500)\s*V(?:DC)?\b/iu', $valueText, $preferred)) {
                $sourceValue = (float) $preferred[1];
            } elseif ($field === 'maximum_system_voltage' && preg_match('/\b(1000|1500)\d\b/u', $valueText, $preferred)) {
                $sourceValue = (float) $preferred[1];
            } elseif ($field === 'nominal_operating_cell_temperature' && preg_match('/\bT\s*=\s*([-+]?\d+(?:[.,]\d+)?)\s*[°º]?\s*C/iu', $valueText, $temperature)) {
                $sourceValue = (float) str_replace(',', '.', $temperature[1]);
            } elseif ($unit === 'kg' && preg_match('/([-+]?\d+(?:[.,]\d+)?)\s*kg\b/iu', $valueText, $kg)) {
                $sourceValue = (float) str_replace(',', '.', $kg[1]);
            } elseif ($unit === 'kg' && preg_match('/([-+]?\d+(?:[.,]\d+)?)\s*lbs?\b/iu', $valueText, $lb)) {
                $sourceValue = round(((float) str_replace(',', '.', $lb[1])) * 0.453592, 2);
            }

            if ($sourceValue === null && ! preg_match('/[-+]?\d+(?:[.,]\d+)?/u', $valueText, $number)) {
                continue;
            }

            $sourceValue ??= (float) str_replace(',', '.', $number[0]);

            return $this->sourceValue(
                value: $sourceValue,
                field: $field,
                sourceText: $match['line'],
                sourcePage: $match['page'],
                unit: $unit,
            );
        }

        return null;
    }

    private function temperatureRangeValue(array $pages, string $field, array $labels): ?ModuleSourceValueDto
    {
        $match = $this->findLabeledValue($pages, $labels);

        if ($match === null) {
            return null;
        }

        $line = mb_strtolower($match['line']);

        if (str_contains($line, 'nominal module operating temperature') || str_contains($line, 'noct') || str_contains($line, 'nmot')) {
            return null;
        }

        if (! preg_match('/[-−]?\s*\d+\s*[°º]?\s*[C℃]?\s*(?:~|to|-)\s*\+?\s*\d+\s*[°º]?\s*[C℃]?/iu', $match['value'], $range)) {
            return $this->sourceValue(
                value: $this->cleanValue($match['value']),
                field: $field,
                sourceText: $match['line'],
                sourcePage: $match['page'],
            );
        }

        return $this->sourceValue(
            value: preg_replace('/\s+/u', '', $range[0]) ?? $range[0],
            field: $field,
            sourceText: $match['line'],
            sourcePage: $match['page'],
            unit: '°C',
        );
    }

    private function percentValue(array $pages, string $field, array $labels): ?ModuleSourceValueDto
    {
        if (in_array($field, ['first_year_degradation_percent', 'annual_degradation_percent'], true)) {
            $before = $this->percentBeforeLabel($pages, $labels, $field);

            if ($before !== null) {
                return $before;
            }
        }

        $match = $this->findLabeledValue($pages, $labels);

        if ($match === null) {
            return null;
        }

        $value = $match['value'] !== '' ? $match['value'] : $match['line'];

        if (! preg_match('/[-+]?\d+(?:[.,]\d+)?\s*%/u', $value, $number)) {
            return null;
        }

        return $this->sourceValue(
            value: (float) str_replace(',', '.', preg_replace('/[^\d.,+-]/u', '', $number[0]) ?? $number[0]),
            field: $field,
            sourceText: $match['line'],
            sourcePage: $match['page'],
            unit: '%',
        );
    }

    private function percentBeforeLabel(array $pages, array $labels, string $field): ?ModuleSourceValueDto
    {
        foreach ($pages as $page => $text) {
            foreach (preg_split('/\R/u', $text) ?: [] as $line) {
                foreach ($labels as $label) {
                    if (preg_match($label, $line, $matches, PREG_OFFSET_CAPTURE) !== 1) {
                        continue;
                    }

                    $before = substr($line, 0, (int) ($matches[0][1] ?? 0));

                    if (! preg_match_all('/[-+]?\d+(?:[.,]\d+)?\s*%/u', $before, $numbers) || ($numbers[0] ?? []) === []) {
                        continue;
                    }

                    $raw = end($numbers[0]);

                    return $this->sourceValue(
                        value: (float) str_replace(',', '.', preg_replace('/[^\d.,+-]/u', '', $raw) ?? $raw),
                        field: $field,
                        sourceText: trim($line),
                        sourcePage: (int) $page,
                        unit: '%',
                    );
                }
            }
        }

        return null;
    }

    private function temperatureCoefficient(array $pages, string $field, array $labels): ?ModuleSourceValueDto
    {
        $match = $this->findLabeledValue($pages, $labels, requireTemperatureContext: true);

        if ($match === null) {
            return null;
        }

        if (! preg_match('/[-+]?\s*\d+(?:[.,]\d+)?\s*%?\s*\/\s*[°º]?\s*C|[-+]?\s*\d+(?:[.,]\d+)?\s*%?\s*\/\s*℃/iu', $match['value'], $number)) {
            return null;
        }

        $raw = preg_replace('/\s+/u', '', $number[0]) ?? $number[0];

        return $this->sourceValue(
            value: (float) str_replace(',', '.', preg_replace('/[^\d.,+-]/u', '', $raw) ?? $raw),
            field: $field,
            sourceText: $match['line'],
            sourcePage: $match['page'],
            unit: '%/°C',
        );
    }

    private function warrantyYears(array $pages, string $field, array $labels): ?ModuleSourceValueDto
    {
        foreach ($pages as $page => $text) {
            foreach (preg_split('/\R/u', $text) ?: [] as $line) {
                foreach ($labels as $label) {
                    if (preg_match($label, $line, $labelMatch) !== 1) {
                        continue;
                    }

                    $year = $labelMatch[1] ?? null;

                    if (! is_string($year) || ! is_numeric($year)) {
                        $after = $this->textAfterMatch($line, $label, $labelMatch);
                        $search = $after !== '' ? $after : $line;

                        if (! preg_match('/\b\d{1,2}\b/u', $search, $number)) {
                            continue;
                        }

                        $year = $number[0];
                    }

                    return $this->sourceValue(
                        value: (int) $year,
                        field: $field,
                        sourceText: trim($line),
                        sourcePage: (int) $page,
                        unit: 'years',
                    );
                }
            }
        }

        return null;
    }

    private function dimensionValue(array $pages): ?ModuleSourceValueDto
    {
        $labels = [
            '/\bModule\s+Dimensions\b/iu',
            '/\bDimensions\b/iu',
            '/\bDimension\b/iu',
        ];

        foreach ($this->findLabeledValues($pages, $labels) as $match) {
            if (! preg_match('/\d+(?:[.,]\d+)?(?:\s*±\s*\d+(?:[.,]\d+)?)?\s*(?:mm)?\s*[×xX]\s*\d+(?:[.,]\d+)?(?:\s*±\s*\d+(?:[.,]\d+)?)?\s*(?:mm)?\s*[×xX]\s*\d+(?:[.,]\d+)?(?:\s*±\s*\d+(?:[.,]\d+)?)?\s*(?:mm|in)?/u', $match['value'], $dimension)) {
                continue;
            }

            $unit = preg_match('/\bin\b/iu', $dimension[0]) === 1 ? 'in' : 'mm';
            $value = $unit === 'in' ? $this->inchesDimensionToMm($dimension[0]) : $dimension[0];

            return $this->sourceValue(
                value: $value,
                field: 'dimensions',
                sourceText: $match['line'],
                sourcePage: $match['page'],
                unit: 'mm',
            );
        }

        return null;
    }

    private function inchesDimensionToMm(string $dimension): string
    {
        preg_match_all('/\d+(?:[.,]\d+)?/u', $dimension, $numbers);

        $parts = [];

        foreach (array_slice($numbers[0] ?? [], 0, 3) as $number) {
            $parts[] = rtrim(rtrim((string) round(((float) str_replace(',', '.', $number)) * 25.4, 1), '0'), '.');
        }

        return implode('×', $parts);
    }

    private function dimensionParts(string $dimension): array
    {
        preg_match_all('/\d+(?:[.,]\d+)?/u', $dimension, $numbers);

        $parts = [];

        foreach ($numbers[0] ?? [] as $number) {
            $parts[] = (float) str_replace(',', '.', $number);
        }

        if (count($parts) > 3 && str_contains($dimension, '±')) {
            return [$parts[0], $parts[2] ?? null, $parts[4] ?? null];
        }

        return array_slice($parts, 0, 3);
    }

    private function dimensionPartValue(array $parts, int $index, ?ModuleSourceValueDto $source, string $field): ?ModuleSourceValueDto
    {
        if (! isset($parts[$index]) || $source === null) {
            return null;
        }

        return $this->sourceValue(
            value: $parts[$index],
            field: $field,
            sourceText: $source->sourceText,
            sourcePage: $source->sourcePage,
            unit: 'mm',
        );
    }

    private function bypassDiodes(?ModuleSourceValueDto $junctionBox): ?ModuleSourceValueDto
    {
        $text = $junctionBox?->sourceText ?? '';

        if (! preg_match('/\b\d+\s+(?:bypass\s+)?diodes?\b/iu', $text, $match)) {
            return null;
        }

        return $this->sourceValue(
            value: trim($match[0]),
            field: 'bypass_diodes',
            sourceText: $text,
            sourcePage: $junctionBox?->sourcePage,
        );
    }

    private function endOfWarrantyOutput(array $pages): ?ModuleSourceValueDto
    {
        foreach ($pages as $page => $text) {
            foreach (preg_split('/\R/u', $text) ?: [] as $line) {
                if (! str_contains(mb_strtolower($line), 'warranty') && ! str_contains(mb_strtolower($line), 'years')) {
                    continue;
                }

                if (! preg_match('/\b(8\d(?:\.\d+)?|9\d(?:\.\d+)?)\s*%/u', $line, $match)) {
                    continue;
                }

                return $this->sourceValue(
                    value: (float) $match[1],
                    field: 'end_of_warranty_output_percent',
                    sourceText: trim($line),
                    sourcePage: (int) $page,
                    unit: '%',
                    confidence: 0.55,
                );
            }
        }

        return null;
    }

    /**
     * @return array{page:int,line:string,value:string}|null
     */
    private function findLabeledValue(
        array $pages,
        array $labels,
        bool $includePreviousNumbers = false,
        bool $requireTemperatureContext = false,
    ): ?array {
        foreach ($this->findLabeledValues($pages, $labels, $includePreviousNumbers, $requireTemperatureContext) as $match) {
            return $match;
        }

        return null;
    }

    /**
     * @return array<int,array{page:int,line:string,value:string}>
     */
    private function findLabeledValues(
        array $pages,
        array $labels,
        bool $includePreviousNumbers = false,
        bool $requireTemperatureContext = false,
    ): array {
        $matches = [];

        foreach ($pages as $page => $text) {
            $lines = preg_split('/\R/u', $text) ?: [];

            foreach ($lines as $index => $line) {
                if ($requireTemperatureContext && ! $this->nearTemperatureContext($lines, $index)) {
                    continue;
                }

                foreach ($labels as $label) {
                    if (preg_match($label, $line, $labelMatch, PREG_OFFSET_CAPTURE) !== 1) {
                        continue;
                    }

                    $value = $this->textAfterMatch($line, $label, $labelMatch);

                    if ($includePreviousNumbers) {
                        $value = trim($line);
                    }

                    if ($value === '' && isset($lines[$index + 1])) {
                        $value = trim($lines[$index + 1]);
                    }

                    $matches[] = [
                        'page' => (int) $page,
                        'line' => trim($line),
                        'value' => $value,
                    ];
                }
            }
        }

        return $matches;
    }

    private function textAfterMatch(string $line, string $pattern, array $match): string
    {
        $matched = $match[0] ?? null;

        if (is_array($matched)) {
            $offset = (int) ($matched[1] ?? 0);
            $length = strlen((string) ($matched[0] ?? ''));

            return trim(substr($line, $offset + $length));
        }

        if (preg_match($pattern, $line, $offsetMatch, PREG_OFFSET_CAPTURE) !== 1) {
            return trim($line);
        }

        $offset = (int) ($offsetMatch[0][1] ?? 0);
        $length = strlen((string) ($offsetMatch[0][0] ?? ''));

        return trim(substr($line, $offset + $length));
    }

    private function nearTemperatureContext(array $lines, int $index): bool
    {
        $slice = implode(' ', array_slice($lines, max(0, $index - 5), 10));
        $slice = mb_strtolower($slice);

        return str_contains($slice, 'temperature')
            || str_contains($slice, 'noct')
            || str_contains($slice, 'nmot');
    }

    private function lineMatches(string $line, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $line) === 1) {
                return true;
            }
        }

        return false;
    }

    private function cleanValue(string $value): string
    {
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function sourceValue(
        string|float|int|null $value,
        string $field,
        ?string $sourceText,
        ?int $sourcePage,
        ?string $unit = null,
        float $confidence = 0.7,
        string|float|int|null $normalizedValue = null,
    ): ModuleSourceValueDto {
        return new ModuleSourceValueDto(
            value: $value,
            unit: $unit,
            sourceText: $sourceText,
            sourcePage: $sourcePage,
            sourceSection: null,
            confidence: $confidence,
            metadata: [
                'method' => 'poppler_layout_text',
                'field' => $field,
            ],
            normalizedValue: $normalizedValue ?? $value,
        );
    }

    private function hasAnyValue(array $array): bool
    {
        foreach ($array as $key => $value) {
            if ($key === 'metadata') {
                continue;
            }

            if ($value !== null && $value !== []) {
                return true;
            }
        }

        return false;
    }
}
