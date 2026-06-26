<?php

namespace App\DeviceScan\Parsing\Module;

class ModuleTypeHeaderParser
{
    public function parseModels(string $text): array
    {
        $moduleTypePos = stripos($text, 'Module Type');

        if ($moduleTypePos === false) {
            return [];
        }

        $window = substr($text, $moduleTypePos, 1200);

        preg_match_all(
            '/JKM\d{3}[A-Z]?N-\d+[A-Z0-9-]*(?:-V)?/i',
            $window,
            $matches
        );

        $models = array_values(array_unique($matches[0] ?? []));

        return $this->deduplicateVariants($models);
    }

    public function parseConditions(string $text): array
    {
        $moduleTypePos = stripos($text, 'Module Type');

        if ($moduleTypePos === false) {
            return ['STC', 'NOCT'];
        }

        $window = substr($text, max(0, $moduleTypePos - 600), 900);

        $hasStc = preg_match('/\bSTC\b/i', $window);
        $hasNoct = preg_match('/\bNOCT\b/i', $window);

        if ($hasStc && $hasNoct) {
            return ['STC', 'NOCT'];
        }

        if ($hasStc) {
            return ['STC'];
        }

        if ($hasNoct) {
            return ['NOCT'];
        }

        return ['STC', 'NOCT'];
    }

    private function deduplicateVariants(array $models): array
    {
        $baseModels = array_values(array_filter(
            $models,
            fn (string $model): bool => ! str_ends_with(strtoupper($model), '-V')
        ));

        return $baseModels !== [] ? $baseModels : $models;
    }
}