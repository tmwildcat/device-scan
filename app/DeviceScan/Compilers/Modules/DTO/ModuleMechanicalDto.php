<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\DTO;

final readonly class ModuleMechanicalDto
{
    public function __construct(
        public ?ModuleSourceValueDto $dimensions = null,
        public ?ModuleSourceValueDto $lengthMm = null,
        public ?ModuleSourceValueDto $widthMm = null,
        public ?ModuleSourceValueDto $thicknessMm = null,
        public ?ModuleSourceValueDto $weightKg = null,
        public ?ModuleSourceValueDto $cellType = null,
        public ?ModuleSourceValueDto $cellCount = null,
        public ?ModuleSourceValueDto $junctionBox = null,
        public ?ModuleSourceValueDto $connector = null,
        public ?ModuleSourceValueDto $cableLength = null,
        public ?ModuleSourceValueDto $glass = null,
        public ?ModuleSourceValueDto $frame = null,
        public ?ModuleSourceValueDto $bypassDiodes = null,
        public ?ModuleSourceValueDto $packaging = null,
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'dimensions' => $this->dimensions?->toArray(),
            'length_mm' => $this->lengthMm?->toArray(),
            'width_mm' => $this->widthMm?->toArray(),
            'thickness_mm' => $this->thicknessMm?->toArray(),
            'weight_kg' => $this->weightKg?->toArray(),
            'cell_type' => $this->cellType?->toArray(),
            'cell_count' => $this->cellCount?->toArray(),
            'junction_box' => $this->junctionBox?->toArray(),
            'connector' => $this->connector?->toArray(),
            'cable_length' => $this->cableLength?->toArray(),
            'glass' => $this->glass?->toArray(),
            'frame' => $this->frame?->toArray(),
            'bypass_diodes' => $this->bypassDiodes?->toArray(),
            'packaging' => $this->packaging?->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}
