<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\Extraction;

use App\DeviceScan\Compilers\Inverters\DTO\InverterAcOutputDto;
use App\DeviceScan\Compilers\Inverters\InverterSectionDetector;
use App\DeviceScan\Compilers\Inverters\InverterTextDocument;

final class InverterAcOutputExtractor extends InverterExtractionSupport
{
    public function extract(InverterTextDocument $document): InverterAcOutputDto
    {
        $models = $this->modelNames($document);

        return new InverterAcOutputDto(
            models: $this->extractRows($document, $models, $this->definitions(), InverterSectionDetector::AC_OUTPUT),
            metadata: [
                'method' => 'poppler_layout_text',
                'model_count' => count($models),
            ],
        );
    }

    private function definitions(): array
    {
        return [
            'rated_ac_power' => ['unit' => 'W', 'patterns' => ['/\bRated\s+(?:AC\s+)?(?:output\s+)?power\b/iu', '/\bNominal\s+AC\s+output\s+power\b/iu', '/\bAC\s+nominal\s+power\b/iu', '/\bAC\s+rated\s+power\b/iu', '/\bAC\s+output\s+power\b/iu', '/\bNominal\s+AC\s+Power\s+Total\b/iu', '/\bRated\s+Power\b/iu']],
            'max_ac_power' => ['unit' => 'W', 'patterns' => ['/\bMax\.?\s+AC\s+(?:output\s+)?power\b/iu', '/\bMax\.?\s+output\s+power\b/iu', '/\bMaximum\s+power\b/iu']],
            'rated_apparent_power' => ['unit' => 'VA', 'patterns' => ['/\bRated\s+(?:AC\s+)?(?:output\s+)?apparent\s+power\b/iu', '/\bRated\s*\/\s*Max\.?\s+apparent\s+power\b/iu', '/\bApparent\s+power\b/iu']],
            'max_apparent_power' => ['unit' => 'VA', 'patterns' => ['/\bMax\.?\s+(?:AC\s+)?(?:output\s+)?apparent\s+power\b/iu', '/\bMax\.?\s+apparent\s+output\s+power\b/iu', '/\bMax\.?\s+apparent\s+power\b/iu']],
            'rated_ac_voltage' => ['unit' => 'V', 'patterns' => ['/\bRated\s+(?:AC\s+|grid\s+)?(?:output\s+)?voltage\b/iu', '/\bRated\s+grid\s+voltage\b/iu', '/\bRated\s+voltage\b/iu', '/\bGrid\s+connection\s*\(V\s*AC,r\)/iu', '/\bNominal\s+AC\s+voltage/u', '/\bNominal\s+output\s+voltage\b/iu', '/\bNominal\s+AC\s+voltages\b/iu', '/\bTypical\s+nominal\s+AC\s+voltages\b/iu', '/\bOperating\s+Grid\s+Voltage\b/iu']],
            'ac_voltage_range' => ['unit' => 'V', 'patterns' => ['/\bAC\s+voltage\s+range\b/iu', '/\bInput\s+voltage\s+range\b/iu', '/\bNominal\s+AC\s+voltage（range\*）/iu']],
            'rated_frequency' => ['unit' => 'Hz', 'patterns' => ['/\bRated\s+(?:AC\s+grid\s+|grid\s+)?frequency\b/iu', '/\bRated\s+grid\s+frequency\b/iu', '/\bNominal\s+grid\s+frequency\b/iu', '/\bOperating\s+Grid\s+Frequency\b/iu', '/\bAC\s+power\s+frequency\b/iu', '/\bFrequency\b/iu', '/\bOutput\s+frequency\b/iu']],
            'frequency_range' => ['unit' => 'Hz', 'patterns' => ['/\bGrid\s+frequency\s+range\b/iu', '/\bfrequency\s+range\b/iu', '/\bAC\s+grid\s+frequency（range\*）/iu']],
            'rated_output_current' => ['unit' => 'A', 'patterns' => ['/\bRated\s+(?:AC\s+|grid\s+)?output\s+current\b/iu', '/\bNominal\s+AC\s+output\s+current\b/iu']],
            'max_output_current' => ['unit' => 'A', 'patterns' => ['/\bMax\.?\s+output\s+current\b/iu', '/\bMax\.?\s+AC\s+output\s+current\b/iu', '/\bMaximum\s+Current\b/iu']],
            'power_factor' => ['unit' => null, 'patterns' => ['/\b(?:Adjustable\s+)?power\s+factor\b/iu', '/\bPower\s+factor\s+at\s+Rated\s+power\b/iu', '/\bPower\s+Factor\s+\(CosPhi\)/iu', '/\bPower\s+Factor\s+Range\b/iu']],
            'thd' => ['unit' => '%', 'patterns' => ['/\b(?:Harmonic|THD|THDi|Max\.?\s+total\s+harmonic\s+distortion|Total\s+harmonic\s+distortion|Current\s+Harmonic\s+Distortion)\b/iu']],
            'phase_type' => ['unit' => null, 'patterns' => ['/\bGrid\s+connection\b/iu', '/\bOperation\s+phase\b/iu', '/\bFeed-in\s+phases\s*\/\s*AC\s+connection\b/iu', '/\bAC\s+grid\s+connection\s+type\b/iu']],
        ];
    }
}
