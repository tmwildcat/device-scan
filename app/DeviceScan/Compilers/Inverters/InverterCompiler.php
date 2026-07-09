<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters;

use App\DeviceScan\Compilers\Inverters\DTO\InverterDto;
use App\DeviceScan\Compilers\Inverters\Extraction\InverterAcOutputExtractor;
use App\DeviceScan\Compilers\Inverters\Extraction\InverterCentralSpecificExtractor;
use App\DeviceScan\Compilers\Inverters\Extraction\InverterDcInputExtractor;
use App\DeviceScan\Compilers\Inverters\Extraction\InverterProtectionExtractor;
use App\DeviceScan\Compilers\Inverters\Extraction\InverterRatedPowerConditionExtractor;
use App\DeviceScan\Compilers\Inverters\Validation\InverterValidator;
use Symfony\Component\Process\Process;

final class InverterCompiler
{
    public function __construct(
        private readonly InverterSectionDetector $sectionDetector,
        private readonly InverterDcInputExtractor $dcInputExtractor,
        private readonly InverterAcOutputExtractor $acOutputExtractor,
        private readonly InverterProtectionExtractor $protectionExtractor,
        private readonly InverterRatedPowerConditionExtractor $ratedPowerConditionExtractor,
        private readonly InverterCentralSpecificExtractor $centralSpecificExtractor,
        private readonly InverterValidator $validator,
    ) {}

    public function compile(string $pdfPath): InverterDto
    {
        $document = $this->loadText($pdfPath);
        $text = $document->text();
        $sections = $this->sectionDetector->detect($document);
        $identity = $this->identity($document);

        if ($this->isUnsupportedAccessory($document)) {
            return $this->withValidation(new InverterDto(
                manufacturer: $identity['manufacturer'],
                series: $identity['series'],
                modelSeries: $identity['model_series'],
                modelName: $identity['model_name'],
                powerClassKw: $identity['power_class_kw'],
                displayName: $identity['display_name'],
                models: [],
                deviceType: 'accessory',
                unsupportedReason: 'backup_box_or_accessory',
                sections: $sections,
                extractionWarnings: ['unsupported_accessory_backup_box'],
                sourceMetadata: $this->sourceMetadata($document, $identity),
            ));
        }

        $dcInput = $this->dcInputExtractor->extract($document);
        $acOutput = $this->acOutputExtractor->extract($document);
        $protection = $this->protectionExtractor->extract($document);
        $ratedPowerConditions = $this->ratedPowerConditionExtractor->extract($document);
        $centralSpecific = $identity['device_type'] === 'central_inverter'
            ? $this->centralSpecificExtractor->extract($document)
            : null;
        $models = array_values(array_unique(array_filter([
            ...array_map(fn ($model) => $model->model, $dcInput->models),
            ...array_map(fn ($model) => $model->model, $acOutput->models),
        ])));
        $warnings = [];

        if ($dcInput->models === [] || $this->allModelsEmpty($dcInput->models)) {
            $warnings[] = 'missing_inverter_dc_input';
        }

        if ($acOutput->models === [] || $this->allModelsEmpty($acOutput->models)) {
            $warnings[] = 'missing_inverter_ac_output';
        }

        return $this->withValidation(new InverterDto(
            manufacturer: $identity['manufacturer'],
            series: $identity['series'],
            modelSeries: $identity['model_series'],
            modelName: $identity['model_name'],
            powerClassKw: $identity['power_class_kw'],
            displayName: $identity['display_name'],
            models: $models,
            deviceType: $identity['device_type'],
            dcInput: $dcInput,
            acOutput: $acOutput,
            ratedPowerConditions: $ratedPowerConditions,
            protection: $protection,
            centralSpecific: $centralSpecific,
            sections: $sections,
            extractionWarnings: $warnings,
            sourceMetadata: [
                ...$this->sourceMetadata($document, $identity),
                'text_length' => strlen($text),
            ],
        ));
    }

    private function withValidation(InverterDto $dto): InverterDto
    {
        $validation = $this->validator->validate($dto);
        $quality = $this->validator->quality($dto, $validation);

        return new InverterDto(
            manufacturer: $dto->manufacturer,
            series: $dto->series,
            modelSeries: $dto->modelSeries,
            modelName: $dto->modelName,
            powerClassKw: $dto->powerClassKw,
            displayName: $dto->displayName,
            models: $dto->models,
            deviceType: $dto->deviceType,
            unsupportedReason: $dto->unsupportedReason,
            dcInput: $dto->dcInput,
            acOutput: $dto->acOutput,
            ratedPowerConditions: $dto->ratedPowerConditions,
            protection: $dto->protection,
            centralSpecific: $dto->centralSpecific,
            validation: $validation,
            extractionQualityScore: $quality['score'],
            extractionQualityGrade: $quality['grade'],
            extractionQualityReasons: $quality['reasons'],
            sections: $dto->sections,
            extractionWarnings: $dto->extractionWarnings,
            sourceMetadata: $dto->sourceMetadata,
        );
    }

    private function loadText(string $pdfPath): InverterTextDocument
    {
        $process = new Process(['pdftotext', '-layout', $pdfPath, '-']);
        $process->setTimeout(30);
        $process->run();

        $pages = [];

        if ($process->isSuccessful()) {
            foreach (preg_split("/\f/u", $process->getOutput()) ?: [] as $index => $text) {
                $text = trim($text);

                if ($text !== '') {
                    $pages[$index + 1] = $text;
                }
            }
        }

        return new InverterTextDocument(
            path: $pdfPath,
            filename: basename($pdfPath),
            pages: $pages,
        );
    }

    private function isUnsupportedAccessory(InverterTextDocument $document): bool
    {
        $haystack = mb_strtolower($document->filename.' '.$document->text());

        return str_contains($haystack, 'syn50-xh-10')
            || str_contains($haystack, 'backup box')
            || str_contains($haystack, 'suitable for min-xh series inverters');
    }

    /**
     * @return array{manufacturer:?string,series:?string,model_series:?string,model_name:?string,power_class_kw:?float,display_name:?string,device_type:string,metadata:array}
     */
    private function identity(InverterTextDocument $document): array
    {
        $metadata = $this->companionMetadata($document);
        $filename = pathinfo($document->filename, PATHINFO_FILENAME);
        $text = $document->text();
        $deviceType = $this->inferDeviceType($document, $metadata);
        $series = $metadata['series'] ?? $this->inferSeries($document);
        $modelName = $metadata['model_name'] ?? $this->inferModelName($document);
        $modelSeries = $this->inferModelSeries($document, $series, $modelName);
        $powerClassKw = $this->inferPowerClassKw($document, $modelName, $filename.' '.$text);
        $displayName = trim(implode(' ', array_filter([$modelSeries, $powerClassKw !== null ? $this->formatPower($powerClassKw) : null])));

        if ($displayName === '') {
            $displayName = $modelName ?: $series ?: $filename;
        }

        return [
            'manufacturer' => $metadata['manufacturer'] ?? $this->inferManufacturer($document),
            'series' => $series,
            'model_series' => $modelSeries,
            'model_name' => $modelName,
            'power_class_kw' => $powerClassKw,
            'display_name' => $displayName,
            'device_type' => $deviceType,
            'metadata' => $metadata,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function companionMetadata(InverterTextDocument $document): array
    {
        $jsonPath = preg_replace('/\.pdf$/iu', '.json', $document->path);

        if (! is_string($jsonPath) || ! is_file($jsonPath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($jsonPath), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function inferManufacturer(InverterTextDocument $document): ?string
    {
        $parts = explode(DIRECTORY_SEPARATOR, $document->path);
        $parent = $parts[count($parts) - 2] ?? null;

        if (is_string($parent) && $parent !== 'inverters' && $parent !== 'central') {
            return match ($parent) {
                'PowerElectronics' => 'Power Electronics',
                'GamesaElectric' => 'Gamesa Electric',
                default => $parent,
            };
        }

        $first = preg_split('/[_-]/', pathinfo($document->filename, PATHINFO_FILENAME))[0] ?? null;

        return is_string($first) && $first !== '' ? $first : null;
    }

    private function inferSeries(InverterTextDocument $document): ?string
    {
        $filename = pathinfo($document->filename, PATHINFO_FILENAME);

        foreach ([
            'SUN2000' => 'SUN2000',
            'Huawei-SUN2000-330KTL-H1' => 'SUN2000',
            'SG5-12RT' => 'SG RT',
            'Sungrow-SG3125HV' => 'SG3125HV',
            'Sungrow-SG4400UD-MV' => 'SG4400UD-MV',
            'Sunny_Tripower_CORE2' => 'Sunny Tripower CORE2',
            'SMA-Sunny-Central-4200-UP' => 'Sunny Central UP',
            'Fronius_Verto' => 'Verto',
            'Fronius_Symo_GEN24' => 'Symo GEN24 Plus',
            'MIN_2500-6000TL-XH' => 'MIN TL-XH',
            'SYN50-XH-10' => 'SYN50-XH-10',
            'FIMER-PVS980-58' => 'PVS980',
            'PowerElectronics-HEMK' => 'HEMK',
            'GamesaElectric-Proteus' => 'Proteus',
            'Sineng-EP-3125-HA' => 'EP',
            'Ingeteam-INGECON-SUN-Power' => 'INGECON SUN PowerMax',
            'TMEIC-Solar-Ware-Samurai' => 'Solar Ware Samurai',
        ] as $needle => $series) {
            if (str_contains($filename, $needle)) {
                return $series;
            }
        }

        return null;
    }

    private function inferModelName(InverterTextDocument $document): ?string
    {
        $filename = pathinfo($document->filename, PATHINFO_FILENAME);

        $map = [
            'Huawei-SUN2000-330KTL-H1' => 'SUN2000-330KTL-H1',
            'Sungrow-SG3125HV' => 'SG3125HV',
            'Sungrow-SG4400UD-MV' => 'SG4400UD-MV-US',
            'SMA-Sunny-Central-4200-UP' => 'SC 4200 UP',
            'FIMER-PVS980-58' => 'PVS980-58',
            'PowerElectronics-HEMK' => 'HEMK',
            'GamesaElectric-Proteus' => 'Proteus PV Inverters',
            'Sineng-EP-3125-HA' => 'EP-3125-HA-UD/10~35',
            'Ingeteam-INGECON-SUN-Power' => 'INGECON SUN PowerMax B Series',
            'TMEIC-Solar-Ware-Samurai' => 'Solar Ware Samurai',
        ];

        foreach ($map as $needle => $modelName) {
            if (str_contains($filename, $needle)) {
                return $modelName;
            }
        }

        return null;
    }

    private function inferModelSeries(InverterTextDocument $document, ?string $series, ?string $modelName): ?string
    {
        $filename = pathinfo($document->filename, PATHINFO_FILENAME);

        if (str_contains($filename, 'Sungrow_SG5-12RT')) {
            return 'SG RT';
        }

        if (str_contains($filename, 'Huawei_SUN2000_8-20KTL-M2')) {
            return 'SUN2000 KTL-M2';
        }

        if (str_contains($filename, 'Growatt_MIN_2500-6000TL-XH')) {
            return 'MIN TL-XH';
        }

        return $modelName ?: $series;
    }

    private function inferPowerClassKw(InverterTextDocument $document, ?string $modelName, string $haystack): ?float
    {
        $filename = pathinfo($document->filename, PATHINFO_FILENAME);

        $map = [
            'Huawei-SUN2000-330KTL-H1' => 330.0,
            'Sungrow-SG3125HV' => 3125.0,
            'Sungrow-SG4400UD-MV' => 4400.0,
            'SMA-Sunny-Central-4200-UP' => 4200.0,
            'FIMER-PVS980-58' => null,
            'GamesaElectric-Proteus' => null,
            'Sineng-EP-3125-HA' => 3125.0,
            'TMEIC-Solar-Ware-Samurai' => null,
        ];

        foreach ($map as $needle => $powerClass) {
            if (str_contains($filename, $needle)) {
                return $powerClass;
            }
        }

        if ($modelName !== null && preg_match('/(?:^|[^0-9])(\d+(?:[.,]\d+)?)\s*(?:kW|KTL|RT|TL)/u', $modelName, $match) === 1) {
            return (float) str_replace(',', '.', $match[1]);
        }

        return null;
    }

    private function inferDeviceType(InverterTextDocument $document, array $metadata): string
    {
        if (($metadata['device_type'] ?? null) === 'central_inverter') {
            return 'central_inverter';
        }

        $path = str_replace(DIRECTORY_SEPARATOR, '/', $document->path);
        $haystack = mb_strtolower($document->filename.' '.$document->text());

        if (str_contains($path, '/inverters/central/')) {
            return 'central_inverter';
        }

        if ($this->isUnsupportedAccessory($document)) {
            return 'accessory';
        }

        if (str_contains($haystack, 'pcs') || str_contains($haystack, 'power conversion system')) {
            return 'pcs';
        }

        if (str_contains($haystack, 'storage inverter') || str_contains($haystack, 'energy storage inverter')) {
            return 'storage_inverter';
        }

        if (str_contains($haystack, 'hybrid') || str_contains($document->filename, 'GEN24') || str_contains($document->filename, 'XH')) {
            return 'hybrid_inverter';
        }

        return 'string_inverter';
    }

    private function formatPower(float $powerKw): string
    {
        return floor($powerKw) === $powerKw ? ((string) (int) $powerKw).'kW' : $powerKw.'kW';
    }

    private function sourceMetadata(InverterTextDocument $document, array $identity): array
    {
        return [
            'filename' => $document->filename,
            'path' => $document->path,
            'page_count' => count($document->pages),
            'compiler' => self::class,
            'identity_source' => $identity['metadata'] !== [] ? 'companion_metadata_or_filename' : 'filename_or_text',
        ];
    }

    private function allModelsEmpty(array $models): bool
    {
        foreach ($models as $model) {
            if ($model->fields !== []) {
                return false;
            }
        }

        return true;
    }
}
