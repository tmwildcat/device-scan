<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\DTO;

final readonly class ModuleSourceValueDto
{
    public function __construct(
        public string|float|int|null $value,
        public ?string $unit = null,
        public ?string $sourceText = null,
        public ?int $sourcePage = null,
        public ?string $sourceSection = null,
        public ?float $confidence = null,
        public array $metadata = [],
        public string|float|int|null $normalizedValue = null,
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
