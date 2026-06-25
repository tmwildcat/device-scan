<?php

namespace App\DeviceScan\Metadata;

class InverterMetadata
{
    /**
     * @return EngineeringField[]
     */
    public static function fields(): array
    {
        return [
            new EngineeringField(
                key: 'make',
                label: 'Manufacturer',
                group: 'General',
                required: true,
                aliases: ['Manufacturer', 'Make', 'Brand']
            ),

            new EngineeringField(
                key: 'model',
                label: 'Model',
                group: 'General',
                required: true,
                aliases: ['Model', 'Model Number', 'Type']
            ),

            new EngineeringField(
                key: 'type',
                label: 'Inverter Type',
                group: 'General',
                type: 'select',
                aliases: ['Inverter Type', 'Type'],
                validation: ['options' => ['string', 'central', 'micro', 'hybrid', 'offgrid', 'other']]
            ),

            new EngineeringField(
                key: 'PnomAC',
                label: 'Nominal AC Power',
                group: 'AC Output',
                type: 'number',
                unit: 'kW',
                required: true,
                aliases: ['Nominal AC Power', 'Rated AC Power', 'AC Nominal Power', 'PnomAC']
            ),

            new EngineeringField(
                key: 'PmaxAC',
                label: 'Maximum AC Power',
                group: 'AC Output',
                type: 'number',
                unit: 'kW',
                aliases: ['Maximum AC Power', 'Max AC Power', 'PmaxAC']
            ),

            new EngineeringField(
                key: 'INomAC',
                label: 'Nominal AC Current',
                group: 'AC Output',
                type: 'number',
                unit: 'A',
                aliases: ['Nominal AC Current', 'Rated AC Current', 'INomAC']
            ),

            new EngineeringField(
                key: 'IMaxAC',
                label: 'Maximum AC Current',
                group: 'AC Output',
                type: 'number',
                unit: 'A',
                aliases: ['Maximum AC Current', 'Max AC Current', 'IMaxAC']
            ),

            new EngineeringField(
                key: 'VoutAC',
                label: 'AC Output Voltage',
                group: 'AC Output',
                type: 'text',
                unit: 'V',
                aliases: ['AC Output Voltage', 'Output Voltage', 'Grid Voltage']
            ),

            new EngineeringField(
                key: 'MonoTri',
                label: 'Phase',
                group: 'AC Output',
                type: 'select',
                aliases: ['Phase', 'Phases', 'Single phase', 'Three phase'],
                validation: ['options' => ['mono', 'tri']]
            ),

            new EngineeringField(
                key: 'freq',
                label: 'Frequency',
                group: 'AC Output',
                type: 'text',
                unit: 'Hz',
                aliases: ['Frequency', 'Grid Frequency', 'Rated Frequency']
            ),

            new EngineeringField(
                key: 'PMaxDC',
                label: 'Maximum DC Power',
                group: 'DC Input',
                type: 'number',
                unit: 'kW',
                aliases: ['Maximum DC Power', 'Max DC Power', 'Recommended Max PV Power']
            ),

            new EngineeringField(
                key: 'VAbsMax',
                label: 'Maximum DC Voltage',
                group: 'DC Input',
                type: 'number',
                unit: 'V',
                aliases: ['Maximum DC Voltage', 'Max DC Voltage', 'Absolute Max DC Voltage']
            ),

            new EngineeringField(
                key: 'VmppMin',
                label: 'MPPT Minimum Voltage',
                group: 'DC Input',
                type: 'number',
                unit: 'V',
                aliases: ['MPPT Min Voltage', 'MPPT Minimum Voltage', 'VmppMin', 'MPP Voltage Range Min']
            ),

            new EngineeringField(
                key: 'VmppMax',
                label: 'MPPT Maximum Voltage',
                group: 'DC Input',
                type: 'number',
                unit: 'V',
                aliases: ['MPPT Max Voltage', 'MPPT Maximum Voltage', 'VmppMax', 'MPP Voltage Range Max']
            ),

            new EngineeringField(
                key: 'IMaxDC',
                label: 'Maximum DC Current',
                group: 'DC Input',
                type: 'number',
                unit: 'A',
                aliases: ['Maximum DC Current', 'Max DC Current', 'Max Input Current']
            ),

            new EngineeringField(
                key: 'NbMPPT',
                label: 'Number of MPPTs',
                group: 'MPPT & Inputs',
                type: 'number',
                aliases: ['Number of MPPTs', 'No. of MPPTs', 'MPPTs', 'NbMPPT']
            ),

            new EngineeringField(
                key: 'NbInputs',
                label: 'Number of Inputs',
                group: 'MPPT & Inputs',
                type: 'number',
                aliases: ['Number of Inputs', 'No. of Inputs', 'PV Inputs', 'String Inputs']
            ),

            new EngineeringField(
                key: 'EfficMax',
                label: 'Maximum Efficiency',
                group: 'Efficiency',
                type: 'number',
                unit: '%',
                aliases: ['Maximum Efficiency', 'Max Efficiency', 'Efficiency']
            ),

            new EngineeringField(
                key: 'EfficEuro',
                label: 'Euro Efficiency',
                group: 'Efficiency',
                type: 'number',
                unit: '%',
                aliases: ['Euro Efficiency', 'European Efficiency']
            ),
        ];
    }

    public static function grouped(): array
    {
        return collect(self::fields())
            ->groupBy(fn (EngineeringField $field) => $field->group)
            ->map(fn ($fields, $group) => [
                'title' => $group,
                'fields' => collect($fields)->map->toArray()->values()->all(),
            ])
            ->values()
            ->all();
    }
}