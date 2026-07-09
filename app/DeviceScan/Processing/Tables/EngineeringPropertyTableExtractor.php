<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables;

use App\DeviceScan\Processing\Sections\DetectedSection;

final class EngineeringPropertyTableExtractor
{
    public function extract(DetectedSection $section): ?DetectedTable
    {
        if (! in_array($section->type, [
            'mechanical',
            'packaging',
            'certification',
            'warranty',
            'features',
        ], true)) {
            return null;
        }

        $rows = [];

        foreach ($section->lines as $line) {
            $line = trim($line);

            if ($line === '' || $line === $section->title) {
                continue;
            }

            $parts = preg_split('/\s{2,}|\t+/u', $line) ?: [];

            if (count($parts) >= 2) {
                $label = trim(array_shift($parts));

                $rows[] = new DetectedTableRow(
                    label: $label,
                    cells: array_map(
                        fn (string $value) => new DetectedTableCell(
                            value: trim($value),
                            displayValue: trim($value),
                        ),
                        $parts,
                    ),
                );

                continue;
            }

            $rows[] = new DetectedTableRow(
                label: $line,
                cells: [],
            );
        }

        if ($rows === []) {
            return null;
        }

        return new DetectedTable(
            title: $section->title,
            page: $section->page,
            models: [],
            rows: $rows,
            metadata: [
                'source' => self::class,
                'section_type' => $section->type,
                'start_line' => $section->startLine,
                'end_line' => $section->endLine,
            ],
        );
    }
}