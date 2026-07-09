<?php

namespace App\LineWatt\Uploads;

final class UploadSecurityResult
{
    /**
     * @param string[] $errors
     * @param string[] $warnings
     * @param array<string,mixed> $metadata
     */
    public function __construct(
        public readonly bool $passed,
        public readonly array $errors = [],
        public readonly array $warnings = [],
        public readonly ?string $sha256 = null,
        public readonly ?int $sizeBytes = null,
        public readonly ?string $mimeType = null,
        public readonly array $metadata = [],
    ) {}

    /**
     * @param string[] $warnings
     * @param array<string,mixed> $metadata
     */
    public static function pass(string $sha256, int $sizeBytes, ?string $mimeType, array $warnings = [], array $metadata = []): self
    {
        return new self(true, [], $warnings, $sha256, $sizeBytes, $mimeType, $metadata);
    }

    /**
     * @param string[] $errors
     * @param string[] $warnings
     * @param array<string,mixed> $metadata
     */
    public static function fail(array $errors, array $warnings = [], ?string $sha256 = null, ?int $sizeBytes = null, ?string $mimeType = null, array $metadata = []): self
    {
        return new self(false, $errors, $warnings, $sha256, $sizeBytes, $mimeType, $metadata);
    }
}
