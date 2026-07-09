<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Engineering\Module\Electrical;

final readonly class CanonicalModuleElectricalCharacteristics
{
    /**
     * @param CanonicalModuleElectricalVariant[] $variants
     */
    public function __construct(
        public array $variants = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'variants' => array_map(
                fn (CanonicalModuleElectricalVariant $variant) => $variant->toArray(),
                $this->variants,
            ),
            'metadata' => $this->metadata,
        ];
    }
}