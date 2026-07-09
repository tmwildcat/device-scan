<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Canonical;

use App\DeviceScan\Processing\Tables\Canonical\Dictionary\ModuleDictionary;

final class ParameterNormalizer
{
    public function __construct(
        private readonly ModuleDictionary $dictionary,
    ) {}

    public function normalize(string $text): ?string
    {
        $normalized = $this->clean($text);

        $parameters = $this->dictionary->parameters();

        uksort(
            $parameters,
            fn (string $a, string $b) => mb_strlen($this->clean($b)) <=> mb_strlen($this->clean($a))
        );

        foreach ($parameters as $phrase => $canonical) {
            if (str_contains($normalized, $this->clean($phrase))) {
                return $canonical;
            }
        }

        return null;
    }

    private function clean(string $text): string
    {
        $text = strtolower($text);

        // Keep contents like Vmp, Imp, Voc, Isc.
        $text = str_replace(['(', ')', '[', ']'], ' ', $text);

        $text = preg_replace('/[^a-z0-9 ]/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}