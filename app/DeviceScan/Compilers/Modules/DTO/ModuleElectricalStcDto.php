<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\DTO;

final readonly class ModuleElectricalStcDto
{
    /**
     * @param ModuleElectricalModelDto[] $models
     */
    public function __construct(
        public array $models = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'models' => array_map(
                fn (ModuleElectricalModelDto $model) => $model->toArray(),
                $this->models,
            ),
            'metadata' => $this->metadata,
        ];
    }
}
