<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\DTO;

final readonly class ModuleWarrantyDto
{
    public function __construct(
        public ?ModuleSourceValueDto $productWarrantyYears = null,
        public ?ModuleSourceValueDto $linearPowerWarrantyYears = null,
        public ?ModuleSourceValueDto $firstYearDegradationPercent = null,
        public ?ModuleSourceValueDto $annualDegradationPercent = null,
        public ?ModuleSourceValueDto $endOfWarrantyOutputPercent = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'product_warranty_years' => $this->productWarrantyYears?->toArray(),
            'linear_power_warranty_years' => $this->linearPowerWarrantyYears?->toArray(),
            'first_year_degradation_percent' => $this->firstYearDegradationPercent?->toArray(),
            'annual_degradation_percent' => $this->annualDegradationPercent?->toArray(),
            'end_of_warranty_output_percent' => $this->endOfWarrantyOutputPercent?->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}
