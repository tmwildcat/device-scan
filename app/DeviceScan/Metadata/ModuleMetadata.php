<?php

namespace App\DeviceScan\Metadata;

class ModuleMetadata
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
                aliases: ['Model', 'Model Number', 'Module Type', 'Type']
            ),

            new EngineeringField(
                key: 'Technol',
                label: 'Technology',
                group: 'General',
                aliases: ['Technology', 'Cell Type', 'Module Technology']
            ),

            new EngineeringField(
                key: 'PNom',
                label: 'Nominal Power',
                group: 'Electrical STC',
                type: 'number',
                unit: 'Wp',
                required: true,
                aliases: ['Maximum Power', 'Rated Power', 'Nominal Power', 'Pmax', 'Power Output']
            ),

            new EngineeringField(
                key: 'Vmp',
                label: 'Maximum Power Voltage',
                group: 'Electrical STC',
                type: 'number',
                unit: 'V',
                required: true,
                aliases: ['Maximum Power Voltage', 'Voltage at Maximum Power', 'Vmp', 'Vmpp']
            ),

            new EngineeringField(
                key: 'Imp',
                label: 'Maximum Power Current',
                group: 'Electrical STC',
                type: 'number',
                unit: 'A',
                required: true,
                aliases: ['Maximum Power Current', 'Current at Maximum Power', 'Imp', 'Impp']
            ),

            new EngineeringField(
                key: 'Voc',
                label: 'Open Circuit Voltage',
                group: 'Electrical STC',
                type: 'number',
                unit: 'V',
                required: true,
                aliases: ['Open-circuit Voltage', 'Open Circuit Voltage', 'Voc', 'OCV']
            ),

            new EngineeringField(
                key: 'Isc',
                label: 'Short Circuit Current',
                group: 'Electrical STC',
                type: 'number',
                unit: 'A',
                aliases: ['Short-circuit Current', 'Short Circuit Current', 'Isc', 'SCC']
            ),

            new EngineeringField(
                key: 'modEff',
                label: 'Module Efficiency',
                group: 'Electrical STC',
                type: 'number',
                unit: '%',
                aliases: ['Module Efficiency', 'Efficiency', 'Module Efficiency STC']
            ),

            new EngineeringField(
                key: 'muPmpReq',
                label: 'Pmax Temperature Coefficient',
                group: 'Temperature',
                type: 'number',
                unit: '%/°C',
                aliases: ['Temperature Coefficient of Pmax', 'Temperature coefficients of Pmax', 'Pmax Temperature Coefficient']
            ),

            new EngineeringField(
                key: 'muVocSpec',
                label: 'Voc Temperature Coefficient',
                group: 'Temperature',
                type: 'number',
                unit: '%/°C',
                aliases: ['Temperature Coefficient of Voc', 'Temperature coefficients of Voc', 'Voc Temperature Coefficient']
            ),

            new EngineeringField(
                key: 'muISC',
                label: 'Isc Temperature Coefficient',
                group: 'Temperature',
                type: 'number',
                unit: '%/°C',
                aliases: ['Temperature Coefficient of Isc', 'Temperature coefficients of Isc', 'Isc Temperature Coefficient']
            ),

            new EngineeringField(
                key: 'noct',
                label: 'NOCT',
                group: 'Temperature',
                type: 'number',
                unit: '°C',
                aliases: ['NOCT', 'Nominal Operating Cell Temperature']
            ),

            new EngineeringField(
                key: 'VMaxIEC',
                label: 'Maximum System Voltage',
                group: 'Limits & Protection',
                type: 'number',
                unit: 'VDC',
                aliases: ['Maximum System Voltage', 'Maximum system voltage']
            ),

            new EngineeringField(
                key: 'BRev',
                label: 'Maximum Series Fuse Rating',
                group: 'Limits & Protection',
                type: 'number',
                unit: 'A',
                aliases: ['Maximum Series Fuse Rating', 'Maximum series fuse rating', 'Series Fuse Rating']
            ),

            new EngineeringField(
                key: 'NCelS',
                label: 'Number of Cells',
                group: 'Mechanical',
                type: 'number',
                aliases: ['No. of cells', 'Number of Cells', 'Cells']
            ),

            new EngineeringField(
                key: 'height',
                label: 'Length',
                group: 'Mechanical',
                type: 'number',
                unit: 'mm',
                aliases: ['Length', 'Module Length']
            ),

            new EngineeringField(
                key: 'width',
                label: 'Width',
                group: 'Mechanical',
                type: 'number',
                unit: 'mm',
                aliases: ['Width', 'Module Width']
            ),

            new EngineeringField(
                key: 'depth',
                label: 'Depth / Frame Height',
                group: 'Mechanical',
                type: 'number',
                unit: 'mm',
                aliases: ['Depth', 'Height', 'Frame Height', 'Thickness']
            ),

            new EngineeringField(
                key: 'weight',
                label: 'Weight',
                group: 'Mechanical',
                type: 'number',
                unit: 'kg',
                aliases: ['Weight', 'Module Weight']
            ),

            new EngineeringField(
                key: 'warranty_years',
                label: 'Product Warranty',
                group: 'Warranty',
                type: 'number',
                unit: 'years',
                aliases: ['Product Warranty', 'Material Warranty']
            ),

            new EngineeringField(
                key: 'warrpow_years',
                label: 'Power Warranty',
                group: 'Warranty',
                type: 'number',
                unit: 'years',
                aliases: ['Linear Power Warranty', 'Power Warranty', 'Performance Warranty']
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