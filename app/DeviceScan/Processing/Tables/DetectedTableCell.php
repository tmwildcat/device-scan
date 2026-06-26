<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables;

final readonly class DetectedTableCell
{
    public function __construct(
        public string|int|float|bool|null $value,
        public ?string $displayValue = null,
        public ?string $unit = null,
    ) {}

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'display_value' => $this->displayValue,
            'unit' => $this->unit,
        ];
    }
}