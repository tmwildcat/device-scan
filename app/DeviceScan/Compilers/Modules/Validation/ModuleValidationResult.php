<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\Validation;

final readonly class ModuleValidationResult
{
    /**
     * @param ModuleValidationIssue[] $issues
     */
    public function __construct(
        public array $issues = [],
    ) {}

    public function hasErrors(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->severity === 'error') {
                return true;
            }
        }

        return false;
    }

    public function toArray(): array
    {
        $counts = [
            'error' => 0,
            'warning' => 0,
            'info' => 0,
        ];

        foreach ($this->issues as $issue) {
            if (array_key_exists($issue->severity, $counts)) {
                $counts[$issue->severity]++;
            }
        }

        return [
            'has_errors' => $this->hasErrors(),
            'counts' => $counts,
            'issues' => array_map(
                fn (ModuleValidationIssue $issue) => $issue->toArray(),
                $this->issues,
            ),
        ];
    }
}
