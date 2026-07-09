<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\DTO;

final readonly class ModulePackagingDto
{
    public function __construct(
        public ?ModuleSourceValueDto $modulesPerPallet = null,
        public ?ModuleSourceValueDto $modulesPerContainer = null,
        public ?ModuleSourceValueDto $palletsPerContainer = null,
        public ?ModuleSourceValueDto $rawPackaging = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'modules_per_pallet' => $this->modulesPerPallet?->toArray(),
            'modules_per_container' => $this->modulesPerContainer?->toArray(),
            'pallets_per_container' => $this->palletsPerContainer?->toArray(),
            'raw_packaging' => $this->rawPackaging?->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}
