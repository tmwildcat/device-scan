<?php

namespace App\DeviceScan\Metadata;

use InvalidArgumentException;

class DeviceMetadata
{
    /**
     * @return EngineeringField[]
     */
    public static function fieldsFor(string $deviceType): array
    {
        return match ($deviceType) {
            'module' => ModuleMetadata::fields(),
            'inverter' => InverterMetadata::fields(),
            default => throw new InvalidArgumentException("Unsupported device type [{$deviceType}]."),
        };
    }

    public static function groupedFor(string $deviceType): array
    {
        return match ($deviceType) {
            'module' => ModuleMetadata::grouped(),
            'inverter' => InverterMetadata::grouped(),
            default => throw new InvalidArgumentException("Unsupported device type [{$deviceType}]."),
        };
    }

    public static function supportedDeviceTypes(): array
    {
        return [
            'module' => 'PV Module',
            'inverter' => 'PV Inverter',
        ];
    }
}