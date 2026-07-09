<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\Extraction;

use App\DeviceScan\Compilers\Modules\DTO\ModuleMechanicalDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleOperatingConditionsDto;
use App\DeviceScan\Compilers\Modules\DTO\ModulePackagingDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleCertificationsDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleTemperatureCharacteristicsDto;
use App\DeviceScan\Compilers\Modules\DTO\ModuleWarrantyDto;

final readonly class ModuleSupplementalExtractionResult
{
    /**
     * @param string[] $warnings
     */
    public function __construct(
        public ?ModuleMechanicalDto $mechanical,
        public ?ModuleOperatingConditionsDto $operatingConditions,
        public ?ModuleTemperatureCharacteristicsDto $temperatureCharacteristics,
        public ?ModuleWarrantyDto $warranty,
        public ?ModulePackagingDto $packaging = null,
        public ?ModuleCertificationsDto $certifications = null,
        public array $warnings = [],
    ) {}
}
