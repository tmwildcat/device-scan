<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\DTO;

final readonly class ModuleCertificationsDto
{
    /**
     * @param ModuleSourceValueDto[] $items
     */
    public function __construct(
        public array $items = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'items' => array_map(
                fn (ModuleSourceValueDto $item) => $item->toArray(),
                $this->items,
            ),
            'metadata' => $this->metadata,
        ];
    }
}
