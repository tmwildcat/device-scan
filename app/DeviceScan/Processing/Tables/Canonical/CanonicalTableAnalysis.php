<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Tables\Canonical;

final readonly class CanonicalTableAnalysis
{
    public function __construct(
        public bool $isSupported,
        public string $canonicalType,
        public float $confidence,
        public int $score,
        public ?string $recommendedInterpreter = null,
        public array $reasons = [],
        public array $metadata = [],
    ) {}

    public function toArray(): array
    {
        return [
            'is_supported' => $this->isSupported,
            'canonical_type' => $this->canonicalType,
            'confidence' => $this->confidence,
            'score' => $this->score,
            'recommended_interpreter' => $this->recommendedInterpreter,
            'reasons' => $this->reasons,
            'metadata' => $this->metadata,
        ];
    }
}