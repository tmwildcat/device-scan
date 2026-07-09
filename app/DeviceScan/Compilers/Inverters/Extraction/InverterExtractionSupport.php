<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\Extraction;

use App\DeviceScan\Compilers\Inverters\DTO\InverterElectricalModelDto;
use App\DeviceScan\Compilers\Inverters\DTO\InverterSourceValueDto;
use App\DeviceScan\Compilers\Inverters\InverterSectionDetector;
use App\DeviceScan\Compilers\Inverters\InverterTextDocument;

abstract class InverterExtractionSupport
{
    /**
     * @return string[]
     */
    protected function modelNames(InverterTextDocument $document): array
    {
        $text = $document->text();
        $filename = pathinfo($document->filename, PATHINFO_FILENAME);

        if (str_contains($filename, 'Huawei_SUN2000_8-20KTL-M2')) {
            return ['SUN2000-8KTL-M2', 'SUN2000-10KTL-M2', 'SUN2000-12KTL-M2', 'SUN2000-15KTL-M2', 'SUN2000-17KTL-M2', 'SUN2000-20KTL-M2'];
        }

        if (str_contains($filename, 'Sungrow_SG5-12RT')) {
            return ['SG5.0RT', 'SG6.0RT', 'SG7.0RT', 'SG8.0RT', 'SG10RT', 'SG12RT'];
        }

        if (str_contains($filename, 'Sungrow_SG80KTL')) {
            return ['SG80KTL'];
        }

        if (str_contains($filename, 'SMA_Sunny_Tripower_CORE2')) {
            return ['STP 110-60'];
        }

        if (str_contains($filename, 'Growatt_MIN_2500-6000TL-XH')) {
            return ['MIN 2500TL-XH', 'MIN 3000TL-XH', 'MIN 3600TL-XH', 'MIN 4200TL-XH', 'MIN 4600TL-XH', 'MIN 5000TL-XH', 'MIN 6000TL-XH'];
        }

        if (str_contains($filename, 'WaareeTech_3P8-15K')) {
            return ['3P8K', '3P10K', '3P12K', '3P15K'];
        }

        if (str_contains($filename, 'Fronius_Symo_GEN24')) {
            return ['Symo GEN24 Plus 3.0', 'Symo GEN24 Plus 4.0', 'Symo GEN24 Plus 5.0', 'Symo GEN24 Plus 6.0', 'Symo GEN24 Plus 8.0', 'Symo GEN24 Plus 10.0'];
        }

        if (str_contains($filename, 'Fronius_Verto')) {
            preg_match_all('/\bVerto\s+(\d{1,2}\.\d)\s+(\d{3}-\d{3}|\d{3})\b/u', $text, $matches, PREG_SET_ORDER);
            $models = [];

            foreach ($matches as $match) {
                $models[] = 'Verto '.$match[1].' '.$match[2];
            }

            return array_values(array_unique($models)) ?: ['Verto'];
        }

        if (str_contains($filename, 'Huawei-SUN2000-330KTL-H1')) {
            return ['SUN2000-330KTL-H1'];
        }

        if (str_contains($filename, 'Sungrow-SG3125HV')) {
            return ['SG3125HV-30', 'SG3400HV-30'];
        }

        if (str_contains($filename, 'Sungrow-SG4400UD-MV')) {
            return ['SG4400UD-MV-US'];
        }

        if (str_contains($filename, 'SMA-Sunny-Central-4200-UP')) {
            return ['SC 4200 UP'];
        }

        if (str_contains($filename, 'FIMER-PVS980-58')) {
            return ['PVS980-58 2.0 MVA', 'PVS980-58 2.1 MVA', 'PVS980-58 2.2 MVA', 'PVS980-58 2.3 MVA'];
        }

        if (str_contains($filename, 'PowerElectronics-HEMK')) {
            return ['HEMK'];
        }

        if (str_contains($filename, 'GamesaElectric-Proteus')) {
            return ['Proteus PV 4100', 'Proteus PV 4300', 'Proteus PV 4500', 'Proteus PV 4700'];
        }

        if (str_contains($filename, 'Sineng-EP-3125-HA')) {
            return ['EP-2500-HA-UD/10~35', 'EP-3125-HA-UD/10~35'];
        }

        if (str_contains($filename, 'Ingeteam-INGECON-SUN-Power')) {
            return ['INGECON SUN PowerMax B Series'];
        }

        if (str_contains($filename, 'TMEIC-Solar-Ware-Samurai')) {
            return ['PVL-L0833GR', 'PVL-L1833GRQ', 'PVL-L1833GRM'];
        }

        preg_match_all('/\b(?:SUN2000-[\dA-Z.-]+|SG\d+(?:\.\d)?RT|SG\d{2,4}[A-Z-]*|STP\s*110-60|MIN\s*\d+TL-XH|PVS980-58|HEMK|PVL-L\d+[A-Z]+)\b/u', $text, $matches);

        return array_values(array_unique(array_map('trim', $matches[0] ?? [])));
    }

    /**
     * @param string[] $models
     * @param array<string,array{patterns:string[],unit:?string}> $definitions
     * @return InverterElectricalModelDto[]
     */
    protected function extractRows(InverterTextDocument $document, array $models, array $definitions, string $section): array
    {
        $fieldsByModel = array_fill_keys($models, []);
        $textFields = ['dc_connection', 'phase_type'];

        foreach ($definitions as $field => $definition) {
            $match = $this->findRow($document, $definition['patterns'], $field);

            if ($match === null) {
                continue;
            }

            $values = in_array($field, $textFields, true)
                ? [trim($match['value'])]
                : $this->valuesFromRow($match['value']);
            $aligned = $this->alignValues($values, count($models));

            foreach ($models as $index => $model) {
                $raw = $aligned[$index] ?? $aligned[0] ?? null;

                if ($raw === null || $raw === '') {
                    continue;
                }

                $fieldsByModel[$model][$field] = new InverterSourceValueDto(
                    value: $this->cleanValue($raw),
                    unit: $definition['unit'],
                    sourceText: $match['line'],
                    sourcePage: $match['page'],
                    sourceSection: $section,
                    confidence: 0.72,
                    metadata: [
                        'method' => 'poppler_layout_text',
                        'field' => $field,
                    ],
                    normalizedValue: $this->normalized($raw),
                );
            }
        }

        $result = [];

        foreach ($fieldsByModel as $model => $fields) {
            $result[] = new InverterElectricalModelDto($model, $fields, ['method' => 'poppler_layout_text']);
        }

        return $result;
    }

    /**
     * @param string[] $patterns
     * @return array{page:int,line:string,value:string}|null
     */
    protected function findRow(InverterTextDocument $document, array $patterns, ?string $field = null): ?array
    {
        foreach ($document->pages as $page => $text) {
            $lines = preg_split('/\R/u', $text) ?: [];

            foreach ($lines as $index => $line) {
                if ($this->shouldSkipLine($line)) {
                    continue;
                }

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $line, $match, PREG_OFFSET_CAPTURE) !== 1) {
                        continue;
                    }

                    $offset = (int) ($match[0][1] ?? 0);
                    $length = strlen((string) ($match[0][0] ?? ''));

                    $value = trim(substr($line, $offset + $length));

                    if ($value === '' && isset($lines[$index + 1])) {
                        $value = trim($lines[$index + 1]);
                    }

                    return [
                        'page' => (int) $page,
                        'line' => trim($line),
                        'value' => $value,
                    ];
                }

                if ($field !== null && $this->compactLineMatchesField($line, $field)) {
                    return [
                        'page' => (int) $page,
                        'line' => trim($line),
                        'value' => trim($line),
                    ];
                }
            }
        }

        return null;
    }

    private function shouldSkipLine(string $line): bool
    {
        $lower = mb_strtolower($line);

        return str_contains($line, '•')
            || str_contains($lower, 'consult ')
            || str_contains($lower, 'features')
            || str_contains($lower, 'grid connection features')
            || str_contains($lower, 'slip mode frequency shift');
    }

    private function compactLineMatchesField(string $line, string $field): bool
    {
        $compact = mb_strtolower(preg_replace('/[^a-z0-9]+/iu', '', $line) ?? $line);
        $needles = [
            'recommended_max_pv_power' => ['recommendedmaxpvpower', 'maxpvarraypower', 'maxusablepvinputpower', 'recommendedpvarraypowerrange'],
            'max_dc_power' => ['maxdcpower', 'maximumdcpower', 'maxpvpower', 'maxusablepvinputpower'],
            'max_dc_voltage' => ['maxinputvoltage', 'maxpvinputvoltage', 'maxdcvoltage', 'maximumdcvoltage', 'maximumvoltage'],
            'startup_voltage' => ['startupvoltage', 'startinputvoltage', 'feedinstartvoltage', 'mininputvoltagestart'],
            'rated_dc_voltage' => ['ratedinputvoltage', 'nominalinputvoltage'],
            'mppt_voltage_range' => ['mppvoltagerange', 'voltagerangempp', 'mpptvoltagerange', 'operatingvoltagerange', 'usablemppvoltagerange', 'dcvoltagerangemppt', 'dcvoltagerangempp', 'dcvoltagerange', 'mpptoperation'],
            'mppt_count' => ['numberofmpptrackers', 'numberofindependentmppinputs', 'noofindependentmppinputs', 'numberofmppt', 'numberofmppttrackers'],
            'strings_per_mppt' => ['stringspermpptracker', 'pvstringspermppt', 'maxnumberofinputs', 'numberofdcconnectionspermppt', 'maxinputstringsnumber'],
            'dc_inputs' => ['numberofdcinputs', 'numberofinputs', 'standardnumberofinputs', 'noofdcinputs'],
            'max_input_current' => ['maxinputcurrentpermpp', 'maxpvinputcurrent', 'maxusableinputcurrent', 'maximumdccurrent', 'maxdccurrent', 'maxdccontinuouscurrent', 'maximumcurrent'],
            'max_short_circuit_current' => ['maxshortcircuitcurrent', 'maxdcshortcircuitcurrent', 'maxmodulearrayshortcircuitcurrent', 'maximumshortcircuitcurrent'],
            'rated_ac_power' => ['ratedoutputpower', 'ratedacpower', 'acnominalpower', 'nominalacoutputpower', 'acoutputpower', 'nominalacpowertotal', 'ratedpower'],
            'max_ac_power' => ['maxacoutputpower', 'maxoutputpower', 'maximumacpower'],
            'rated_apparent_power' => ['ratedacapparentpower', 'ratedapparentpower'],
            'max_apparent_power' => ['maxapparentpower', 'maxacoutputapparentpower', 'maxapparentoutputpower', 'maximumpower'],
            'rated_ac_voltage' => ['ratedoutputvoltage', 'ratedacvoltage', 'ratedgridvoltage', 'ratedvoltage', 'gridconnectionvacr', 'nominalacvoltage', 'nominaloutputvoltage', 'operatinggridvoltage', 'typicalnominalacvoltages'],
            'ac_voltage_range' => ['acvoltagerange', 'inputvoltagerange'],
            'rated_frequency' => ['ratedacgridfrequency', 'ratedgridfrequency', 'nominalgridfrequency', 'operatinggridfrequency', 'acpowerfrequency', 'outputfrequency', 'ratedfrequency'],
            'frequency_range' => ['gridfrequencyrange', 'frequencyrange'],
            'max_output_current' => ['maxoutputcurrent'],
            'rated_output_current' => ['ratedoutputcurrent', 'ratedgridoutputcurrent', 'nominalacoutputcurrent', 'nominalaccurrent'],
            'power_factor' => ['adjustablepowerfactor', 'powerfactoratratedpower', 'powerfactorcosphi', 'powerfactorrange'],
            'thd' => ['maxtotalharmonicdistortion', 'totalharmonicdistortion', 'currentharmonicdistortion', 'harmonicthd', 'thdi'],
            'phase_type' => ['gridconnection', 'operationphase', 'feedinphasesacconnection', 'acgridconnectiontype'],
        ];

        foreach ($needles[$field] ?? [] as $needle) {
            if (str_contains($compact, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    protected function valuesFromRow(string $value): array
    {
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? trim($value);
        $value = preg_replace('/\bkV\s+A\b/iu', 'kVA', $value) ?? $value;
        $value = preg_replace('/\bV\s+A\b/iu', 'VA', $value) ?? $value;
        $value = preg_replace('/\bV\s*dc\b/iu', 'Vdc', $value) ?? $value;

        if ($value === '') {
            return [];
        }

        preg_match_all('/[-+]?\d+(?:[.,]\d+)?\s*(?:MW|MVA|kWp|kW|Wp|W|kVA|VA|kV|Vdc|V|Arms|A|Hz|%|Wpeak)?(?:\s*[\/~–-]\s*[-+]?\d+(?:[.,]\d+)?\s*(?:kV|V|A|Hz|%)?)?(?:\s*@\s*[-+]?\d+(?:[.,]\d+)?\s*(?:℃|°\s*C|ºC|C))?|<\s*\d+(?:[.,]\d+)?\s*%|≤\s*\d+(?:[.,]\d+)?\s*%|[0-9.]+\s*(?:leading|lagging|overexcited|underexcited).*|Single phase|Three phase|3\s*\/\s*3-PE|3\s*\/\s*N\s*\/\s*PE[^,]*(?:,\s*\d+\s*\/\s*\d+\s*V)?/iu', $value, $matches);
        $values = array_values(array_filter(array_map('trim', $matches[0] ?? [])));

        while (
            count($values) > 1
            && preg_match('/^[-+]?\d+(?:[.,]\d+)?$/u', $values[0]) === 1
            && $this->numberForComparison($values[0]) < 10
            && max(array_map(fn (string $item) => $this->numberForComparison($item), $values)) >= 1000
        ) {
            array_shift($values);
        }

        return $values !== [] ? $values : [$value];
    }

    private function numberForComparison(string $raw): float
    {
        $number = preg_replace('/[^\d.,+-]/u', '', $raw) ?: '0';

        if (preg_match('/\d+,\d{3}(?:\D|$)/u', $number) === 1) {
            $number = str_replace(',', '', $number);
        } else {
            $number = str_replace(',', '.', $number);
        }

        return (float) $number;
    }

    /**
     * @param string[] $values
     * @return array<int,string>
     */
    protected function alignValues(array $values, int $modelCount): array
    {
        if ($values === []) {
            return [];
        }

        if ($modelCount > 1 && count($values) >= $modelCount) {
            return array_slice($values, 0, $modelCount);
        }

        return array_fill(0, max(1, $modelCount), implode(' ', $values));
    }

    protected function cleanValue(string $raw): string|float|int
    {
        $raw = trim($raw);

        if (preg_match('/^[-+]?\d+(?:[.,]\d+)?$/u', $raw) === 1) {
            $number = $this->parseNumber($raw);

            return floor($number) === $number ? (int) $number : $number;
        }

        return $raw;
    }

    protected function normalized(string $raw): string|float|int|null
    {
        if (! preg_match('/[-+]?\d+(?:[.,]\d+)?/u', $raw, $match)) {
            return trim($raw) !== '' ? trim($raw) : null;
        }

        return $this->parseNumber($match[0]);
    }

    private function parseNumber(string $raw): float
    {
        $number = trim($raw);

        if (preg_match('/^\d{1,3}(?:,\d{3})+(?:\.\d+)?$/u', $number) === 1) {
            return (float) str_replace(',', '', $number);
        }

        if (preg_match('/^\d+,\d{1,2}$/u', $number) === 1) {
            return (float) str_replace(',', '.', $number);
        }

        return (float) str_replace(',', '', $number);
    }
}
