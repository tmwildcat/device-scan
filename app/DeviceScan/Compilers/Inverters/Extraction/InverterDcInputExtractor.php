<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\Extraction;

use App\DeviceScan\Compilers\Inverters\DTO\InverterDcInputDto;
use App\DeviceScan\Compilers\Inverters\InverterSectionDetector;
use App\DeviceScan\Compilers\Inverters\InverterTextDocument;

final class InverterDcInputExtractor extends InverterExtractionSupport
{
    public function extract(InverterTextDocument $document): InverterDcInputDto
    {
        $models = $this->modelNames($document);

        return new InverterDcInputDto(
            models: $this->extractRows($document, $models, $this->definitions(), InverterSectionDetector::DC_INPUT),
            metadata: [
                'method' => 'poppler_layout_text',
                'model_count' => count($models),
            ],
        );
    }

    private function definitions(): array
    {
        return [
            'recommended_max_pv_power' => ['unit' => 'W', 'patterns' => ['/\bRecommended\s+max\.?\s+PV(?:\s+input)?\s+power\b/iu', '/\bRecommended\s+PV\s+array\s+power\s+range\b/iu', '/\bMax\.?\s+usable\s+PV\s+input\s+power\b/iu', '/\bMax\.?\s+PV\s+array\s+power\b/iu', '/\bMax\.?\s+PV\s+generator\s+output\b/iu', '/\bMax\.?\s+recommended\s+PV\s+power\b/iu']],
            'max_dc_power' => ['unit' => 'W', 'patterns' => ['/\bMax\.?\s+DC\s+power\b/iu', '/\bMaximum\s+DC\s+power\b/iu']],
            'max_dc_voltage' => ['unit' => 'V', 'patterns' => ['/\bMax\.?\s+(?:PV\s+)?input\s+voltage\b/iu', '/\bMax\.?\s+DC\s+voltage\b/iu', '/\bMax\.?\s+PV\s+input\s+voltage\b/iu', '/\bMaximum\s+DC\s+voltage\b/iu', '/\bMaximum\s+voltage\b/iu']],
            'startup_voltage' => ['unit' => 'V', 'patterns' => ['/\bStart(?:-up)?\s+(?:input\s+)?voltage\b/iu', '/\bFeed-in\s+start\s+voltage\b/iu', '/\bMin\.?\s+input\s+voltage\s*\/\s*Start\s+input\s+voltage\b/iu']],
            'rated_dc_voltage' => ['unit' => 'V', 'patterns' => ['/\bRated\s+input\s+voltage\b/iu', '/\bNominal\s+input\s+voltage\b/iu']],
            'mppt_voltage_range' => ['unit' => 'V', 'patterns' => ['/\bMPP(?:T)?\s+voltage\s+range\b/iu', '/\bVoltage\s+Range\s+MPP\b/iu', '/\bUsable\s+MPP\s+voltage\s+range\b/iu', '/\bOperating\s+voltage\s+range\b/iu', '/\bDC\s+input\s+voltage\s+range\b/iu', '/\bDC\s+Voltage\s+Range\s+MPPT\b/iu', '/\bDC\s+voltage\s+range,\s*mpp\b/iu', '/\bDC\s+Voltage\s+Range\b/iu', '/\bMPPT\s+Operation\b/iu']],
            'full_power_mppt_range' => ['unit' => 'V', 'patterns' => ['/\bFull\s+Power\s+MPP(?:T)?\s+Voltage\s+Range\b/iu']],
            'mppt_count' => ['unit' => null, 'patterns' => ['/\bNumber\s+of\s+(?:independent\s+)?MPP(?:T)?\s+(?:trackers|inputs)\b/iu', '/\bNo\.?\s+of\s+(?:independent\s+)?MPP(?:T)?s?\b/iu', '/\bNo\.?\s+of\s+(?:independent\s+)?MPP(?:T)?\s+inputs\b/iu', '/\bNumber\s+of\s+MPPT\s+trackers\b/iu', '/\bNumber\s+of\s+MPPT\b/iu']],
            'strings_per_mppt' => ['unit' => null, 'patterns' => ['/\b(?:No\.?\s+of\s+)?PV\s+strings\s+per\s+MPP(?:T)?(?:\s+tracker)?\b/iu', '/\bStrings\s+per\s+MPP(?:T)?\s+tracker\b/iu', '/\bNumber\s+of\s+DC\s+connections\s+per\s+MPPT\b/iu', '/\bMax\.?\s+number\s+of\s+(?:PV\s+strings|inputs)(?:\s+per\s+MPPT)?\b/iu', '/\bMPPT\s+number\s*\/\s*Max\.?\s+input\s+strings\s+number\b/iu']],
            'dc_inputs' => ['unit' => null, 'patterns' => ['/\bNumber\s+of\s+DC\s+inputs\b/iu', '/\bNo\.?\s+of\s+DC\s+inputs\b/iu', '/\bNumber\s+of\s+protected\s+DC\s+inputs\b/iu', '/\bStandard\s+Number\s+of\s+Inputs\b/iu', '/\bNumber\s+of\s+Inputs\b/iu']],
            'max_input_current' => ['unit' => 'A', 'patterns' => ['/\bMax\.?\s+(?:PV\s+)?input\s+current(?:\s+per\s+MPP(?:T)?(?:\s+tracker)?)?\b/iu', '/\bMax\.?\s+usable\s+input\s+current(?:\s+per\s+MPP(?:T)?)?/iu', '/\bMaximum\s+DC\s+current\b/iu', '/\bMax\.?\s+DC\s+Current\b/iu', '/\bMax\.?\s+DC\s+Continuous\s+Current\b/iu', '/\bMaximum\s+current\b/iu']],
            'max_short_circuit_current' => ['unit' => 'A', 'patterns' => ['/\bMax\.?\s+(?:DC\s+)?short-circuit\s+current\b/iu', '/\bMax\.?\s+module\s+array\s+short\s+circuit\s+current(?:\s+per\s+MPP(?:T)?)?/iu', '/\bMaximum\s+Short-circuit\s+Current\b/iu']],
            'dc_connection' => ['unit' => null, 'patterns' => ['/\bDC\s+connection(?:\s+type)?\b/iu', '/\bDC\s+connection\s*\/\s*AC\s+connection\b/iu']],
        ];
    }
}
