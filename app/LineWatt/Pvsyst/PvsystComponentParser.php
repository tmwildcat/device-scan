<?php

namespace App\LineWatt\Pvsyst;

use Illuminate\Http\UploadedFile;
use ZipArchive;

class PvsystComponentParser
{
    /**
     * @return array{fields:array<string,array<string,mixed>>,warnings:list<string>,raw_rows:list<list<string>>}
     */
    public function parsePaste(string $input, string $deviceType, string $template): array
    {
        $rows = $this->rowsFromText($input);

        return $this->mapRows($rows, $deviceType, $template, 'paste');
    }

    /**
     * @return array{fields:array<string,array<string,mixed>>,warnings:list<string>,raw_rows:list<list<string>>}
     */
    public function parseXlsx(UploadedFile $file, string $deviceType, string $template): array
    {
        $rows = $this->rowsFromXlsx($file->getPathname());
        $mapped = $this->mapLabelRows($rows, $deviceType);

        if ($mapped['fields'] !== []) {
            return [
                'fields' => $mapped['fields'],
                'warnings' => $mapped['warnings'],
                'raw_rows' => $rows,
            ];
        }

        return $this->mapRows($rows, $deviceType, $template, 'xlsx_sequence_fallback');
    }

    /**
     * @return array<string,string>
     */
    public function template(string $deviceType, string $template): array
    {
        if ($deviceType === 'inverter') {
            return [
                'rated_ac_power' => 'W',
                'max_ac_power' => 'W',
                'rated_apparent_power' => 'VA',
                'max_dc_voltage' => 'V',
                'startup_voltage' => 'V',
                'rated_dc_voltage' => 'V',
                'mppt_voltage_range' => 'V',
                'mppt_count' => null,
                'strings_per_mppt' => null,
                'max_input_current' => 'A',
                'max_short_circuit_current' => 'A',
                'rated_ac_voltage' => 'V',
                'rated_frequency' => 'Hz',
                'rated_output_current' => 'A',
                'max_output_current' => 'A',
                'power_factor' => null,
                'thd' => '%',
            ];
        }

        return [
            'rated_max_power_w' => 'W',
            'open_circuit_voltage_v' => 'V',
            'maximum_power_voltage_v' => 'V',
            'short_circuit_current_a' => 'A',
            'maximum_power_current_a' => 'A',
            'module_efficiency_percent' => '%',
            'length_mm' => 'mm',
            'width_mm' => 'mm',
            'thickness_mm' => 'mm',
            'weight_kg' => 'kg',
            'maximum_system_voltage' => 'V',
            'maximum_series_fuse_rating' => 'A',
            'temperature_coefficient_pmax' => '%/°C',
            'temperature_coefficient_voc' => '%/°C',
            'temperature_coefficient_isc' => '%/°C',
        ];
    }

    /**
     * @return array{fields:array<string,array<string,mixed>>,warnings:list<string>}
     */
    private function mapLabelRows(array $rows, string $deviceType): array
    {
        $fields = [];

        foreach ($rows as $row) {
            for ($i = 0; $i < count($row) - 1; $i++) {
                $label = trim((string) ($row[$i] ?? ''));
                $value = trim((string) ($row[$i + 1] ?? ''));
                $field = $this->labelToField($label, $deviceType);

                if ($field && $value !== '') {
                    $fields[$field] = $this->sourceValue($value, $this->unitForField($field), $label.' '.$value, 'xlsx_label');
                }
            }
        }

        return [
            'fields' => $fields,
            'warnings' => $fields === [] ? ['pvsyst_xlsx_labels_not_detected'] : [],
        ];
    }

    /**
     * @return array{fields:array<string,array<string,mixed>>,warnings:list<string>,raw_rows:list<list<string>>}
     */
    private function mapRows(array $rows, string $deviceType, string $template, string $method): array
    {
        $values = [];

        foreach ($rows as $row) {
            foreach ($row as $cell) {
                $cell = trim((string) $cell);

                if ($cell !== '') {
                    $values[] = $cell;
                }
            }
        }

        $fields = [];
        $map = $this->template($deviceType, $template);
        $index = 0;

        foreach ($map as $field => $unit) {
            if (! array_key_exists($index, $values)) {
                break;
            }

            $fields[$field] = $this->sourceValue($values[$index], $unit, $values[$index], $method, $index + 1);
            $index++;
        }

        return [
            'fields' => $fields,
            'warnings' => count($fields) < count($map) ? ['pvsyst_sequence_mapping_partial'] : [],
            'raw_rows' => $rows,
        ];
    }

    private function labelToField(string $label, string $deviceType): ?string
    {
        $label = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $label) ?? '');

        $map = $deviceType === 'inverter'
            ? [
                'max dc voltage' => 'max_dc_voltage',
                'maximum dc voltage' => 'max_dc_voltage',
                'startup voltage' => 'startup_voltage',
                'rated dc voltage' => 'rated_dc_voltage',
                'mppt voltage range' => 'mppt_voltage_range',
                'number of mppt' => 'mppt_count',
                'mppt count' => 'mppt_count',
                'max input current' => 'max_input_current',
                'short circuit current' => 'max_short_circuit_current',
                'rated ac power' => 'rated_ac_power',
                'max ac power' => 'max_ac_power',
                'rated apparent power' => 'rated_apparent_power',
                'rated ac voltage' => 'rated_ac_voltage',
                'frequency' => 'rated_frequency',
                'rated output current' => 'rated_output_current',
                'max output current' => 'max_output_current',
                'power factor' => 'power_factor',
                'thd' => 'thd',
            ]
            : [
                'nominal power' => 'rated_max_power_w',
                'rated power' => 'rated_max_power_w',
                'pmax' => 'rated_max_power_w',
                'voc' => 'open_circuit_voltage_v',
                'open circuit voltage' => 'open_circuit_voltage_v',
                'vmp' => 'maximum_power_voltage_v',
                'vmpp' => 'maximum_power_voltage_v',
                'isc' => 'short_circuit_current_a',
                'short circuit current' => 'short_circuit_current_a',
                'imp' => 'maximum_power_current_a',
                'impp' => 'maximum_power_current_a',
                'efficiency' => 'module_efficiency_percent',
                'length' => 'length_mm',
                'width' => 'width_mm',
                'thickness' => 'thickness_mm',
                'weight' => 'weight_kg',
                'max system voltage' => 'maximum_system_voltage',
                'fuse rating' => 'maximum_series_fuse_rating',
            ];

        foreach ($map as $needle => $field) {
            if (str_contains($label, $needle)) {
                return $field;
            }
        }

        return null;
    }

    private function sourceValue(string $value, ?string $unit, string $sourceText, string $method, ?int $sequence = null): array
    {
        return [
            'value' => $value,
            'unit' => $unit,
            'source_text' => $sourceText,
            'source_page' => null,
            'source_section' => 'PVSYST_IMPORT',
            'confidence' => $method === 'xlsx_label' ? 0.86 : 0.68,
            'normalized_value' => $this->numeric($value),
            'metadata' => [
                'method' => $method,
                'sequence' => $sequence,
                'source' => 'PVSyst Import',
            ],
        ];
    }

    private function numeric(string $value): float|int|string
    {
        if (preg_match('/-?\d+(?:\.\d+)?/', str_replace(',', '.', $value), $match)) {
            $number = (float) $match[0];

            return floor($number) === $number ? (int) $number : $number;
        }

        return $value;
    }

    private function unitForField(string $field): ?string
    {
        foreach (['module', 'inverter'] as $deviceType) {
            $template = $this->template($deviceType, '');

            if (array_key_exists($field, $template)) {
                return $template[$field];
            }
        }

        return null;
    }

    /**
     * @return list<list<string>>
     */
    private function rowsFromText(string $input): array
    {
        return array_values(array_filter(array_map(function (string $line): array {
            $delimiter = str_contains($line, "\t") ? "\t" : (str_contains($line, ';') ? ';' : ',');

            return array_map('trim', str_getcsv($line, $delimiter));
        }, preg_split('/\r\n|\r|\n/', trim($input)) ?: [])));
    }

    /**
     * @return list<list<string>>
     */
    private function rowsFromXlsx(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return [];
        }

        $shared = $this->sharedStrings($zip);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml') ?: '';
        $xml = simplexml_load_string($sheet);
        $rows = [];

        if ($xml !== false) {
            foreach ($xml->sheetData->row ?? [] as $row) {
                $cells = [];

                foreach ($row->c as $cell) {
                    $type = (string) ($cell['t'] ?? '');
                    $raw = (string) ($cell->v ?? '');
                    $cells[] = $type === 's' ? ($shared[(int) $raw] ?? '') : $raw;
                }

                $rows[] = $cells;
            }
        }

        $zip->close();

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml') ?: '');
        $strings = [];

        if ($xml !== false) {
            foreach ($xml->si as $item) {
                $strings[] = (string) ($item->t ?? '');
            }
        }

        return $strings;
    }
}
