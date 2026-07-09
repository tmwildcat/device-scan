<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\Validation;

final readonly class InverterValidationResult
{
    /**
     * @param InverterValidationIssue[] $issues
     */
    public function __construct(
        public array $issues = [],
        public array $summary = [],
    ) {}

    public function countBySeverity(string $severity): int
    {
        return count(array_filter(
            $this->issues,
            fn (InverterValidationIssue $issue) => $issue->severity === $severity,
        ));
    }

    public function toArray(): array
    {
        return [
            'issues' => array_map(
                fn (InverterValidationIssue $issue) => $issue->toArray(),
                $this->issues,
            ),
            'summary' => $this->summary,
        ];
    }
}
