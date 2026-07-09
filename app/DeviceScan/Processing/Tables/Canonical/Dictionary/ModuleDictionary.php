<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Canonical\Dictionary;

final class ModuleDictionary
{
    public function parameters(): array
    {
        return [

            'rated maximum power' => 'rated_max_power',
            'maximum power' => 'rated_max_power',
            'pmax' => 'rated_max_power',

            'maximum power voltage' => 'maximum_power_voltage',
            'operating voltage' => 'maximum_power_voltage',
            'vmp' => 'maximum_power_voltage',

            'maximum power current' => 'maximum_power_current',
            'operating current' => 'maximum_power_current',
            'current at maximum power' => 'maximum_power_current',
            'imp' => 'maximum_power_current',
            'impp' => 'maximum_power_current',

            'open circuit voltage' => 'open_circuit_voltage',
            'voc' => 'open_circuit_voltage',

            'short circuit current' => 'short_circuit_current',
            'isc' => 'short_circuit_current',

            'module efficiency' => 'module_efficiency',

            'power tolerance' => 'power_tolerance',

            'temperature coefficient of pmax' => 'temperature_coefficient_pmax',
            'temperature coefficient of voc' => 'temperature_coefficient_voc',
            'temperature coefficient of isc' => 'temperature_coefficient_isc',

            'nominal operating cell temperature' => 'noct',

            'maximum system voltage' => 'maximum_system_voltage',

            'maximum series fuse rating' => 'maximum_series_fuse_rating',

            'bifaciality coefficient' => 'bifaciality',

        ];
    }
}
