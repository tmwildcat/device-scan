<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Canonical;

use App\DeviceScan\Processing\Tables\DTO\TableCell;
use App\DeviceScan\Processing\Tables\DTO\TableGrid;

final class ModelHeaderResolver
{
    /**
     * @return string[]
     */
    public function models(TableGrid $grid): array
    {
        $candidates = [];

        foreach ($grid->cells as $cell) {
            foreach ($this->extractModelsFromText($cell->text) as $model) {
                $candidates[$model] = true;
            }
        }

        $models = array_keys($candidates);

        usort($models, fn (string $a, string $b) => (int) $a <=> (int) $b);

        return $models;
    }

    /**
     * @return array<int, string>
     */
    public function valueColumns(TableGrid $grid): array
    {
        $columns = [];

        foreach ($grid->cells as $cell) {
            $models = $this->extractModelsFromText($cell->text);

            if ($models !== []) {
                $columns[$cell->column] = $models[0];
            }
        }

        ksort($columns);

        return $columns;
    }

    /**
     * @return string[]
     */
    private function extractModelsFromText(string $text): array
    {
        preg_match_all('/(?:JKM)?(5\d{2}|6\d{2})(?:N|M|W|wp|Wp)?/i', $text, $matches);

        $models = [];

        foreach ($matches[1] ?? [] as $match) {
            $value = (int) $match;

            if ($value >= 300 && $value <= 800) {
                $models[(string) $value] = true;
            }
        }

        return array_keys($models);
    }
}