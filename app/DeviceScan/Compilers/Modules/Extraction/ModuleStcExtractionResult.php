<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\Extraction;

use App\DeviceScan\Compilers\Modules\DTO\ModuleElectricalStcDto;

final readonly class ModuleStcExtractionResult
{
    /**
     * @param string[] $warnings
     */
    public function __construct(
        public ?ModuleElectricalStcDto $dto,
        public array $warnings = [],
    ) {}
}
