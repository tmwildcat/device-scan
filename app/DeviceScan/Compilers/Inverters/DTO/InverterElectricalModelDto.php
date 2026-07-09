<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\DTO;

final readonly class InverterElectricalModelDto
{
    public function __construct(
        public string $model,
        public array $fields = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'fields' => array_map(
                fn (InverterSourceValueDto $value) => $value->toArray(),
                $this->fields,
            ),
            'metadata' => $this->metadata,
        ];
    }
}
