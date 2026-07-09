<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\DTO;

final readonly class InverterDcInputDto
{
    /**
     * @param InverterElectricalModelDto[] $models
     */
    public function __construct(
        public array $models = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'models' => array_map(
                fn (InverterElectricalModelDto $model) => $model->toArray(),
                $this->models,
            ),
            'metadata' => $this->metadata,
        ];
    }
}
