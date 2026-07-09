<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Modules;

use App\DeviceScan\Processing\Sections\DetectedSection;
use App\DeviceScan\Processing\Tables\DetectedTable;
use App\DeviceScan\Processing\Tables\DetectedTableCell;
use App\DeviceScan\Processing\Tables\DetectedTableRow;

final class ModuleMechanicalTableExtractor
{
    private const LABELS = [
        'Cell Type',
        'No. of cells',
        'Dimensions',
        'Weight',
        'Front Glass',
        'Frame',
        'Junction Box',
        'Output Cables',
    ];

    public function extract(DetectedSection $section): ?DetectedTable
    {
        if ($section->type !== 'mechanical') {
            return null;
        }

        $rows = [];

        foreach (self::LABELS as $index => $label) {
            $value = $this->valueAfterLabel($section->lines, $label, self::LABELS[$index + 1] ?? null);

            if ($value === null) {
                continue;
            }

            $rows[] = new DetectedTableRow(
                label: $label,
                cells: [
                    new DetectedTableCell(
                        value: $value,
                        displayValue: $value,
                    ),
                ],
            );
        }

        if ($rows === []) {
            return null;
        }

        return new DetectedTable(
            title: 'Mechanical Characteristics',
            page: $section->page,
            rows: $rows,
            metadata: [
                'source' => self::class,
                'section_type' => $section->type,
                'start_line' => $section->startLine,
                'end_line' => $section->endLine,
            ],
        );
    }

    private function valueAfterLabel(array $lines, string $label, ?string $nextLabel): ?string
    {
        $start = null;

        foreach ($lines as $i => $line) {
            if (mb_strtolower(trim($line)) === mb_strtolower($label)) {
                $start = $i + 1;
                break;
            }
        }

        if ($start === null) {
            return null;
        }

        $end = count($lines);

        if ($nextLabel !== null) {
            foreach ($lines as $i => $line) {
                if ($i <= $start) {
                    continue;
                }

                if (mb_strtolower(trim($line)) === mb_strtolower($nextLabel)) {
                    $end = $i;
                    break;
                }
            }
        }

        $valueLines = array_values(array_filter(
            array_slice($lines, $start, max(0, $end - $start)),
            fn (string $line) => trim($line) !== ''
        ));

        return $valueLines === [] ? null : trim(implode(' ', $valueLines));
    }
}