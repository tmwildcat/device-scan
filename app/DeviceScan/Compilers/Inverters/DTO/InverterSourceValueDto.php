<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\DTO;

final readonly class InverterSourceValueDto
{
    public function __construct(
        public string|float|int|bool|array|null $value,
        public ?string $unit = null,
        public ?string $sourceText = null,
        public ?int $sourcePage = null,
        public ?string $sourceSection = null,
        public ?float $confidence = null,
        public array $metadata = [],
        public string|float|int|bool|array|null $normalizedValue = null,
    ) {}

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'unit' => $this->unit,
            'source_text' => $this->sourceText,
            'source_page' => $this->sourcePage,
            'source_section' => $this->sourceSection,
            'confidence' => $this->confidence,
            'normalized_value' => $this->normalizedValue,
            'metadata' => $this->metadata,
        ];
    }
}
