<?php

namespace App\LegalGovernance\Data;

use Illuminate\Support\Collection;

final readonly class LegalAccessDecision
{
    public function __construct(
        public bool $allowed,
        public bool $requiresAcceptance,
        public bool $hasBlockingObligations,
        public bool $configurationValid,
        public Collection $blockingObligations,
        public Collection $nonBlockingObligations,
        public ?string $workflowKey,
        public ?string $capability,
        public string $reasonCode,
        public string $message,
    ) {}
}
