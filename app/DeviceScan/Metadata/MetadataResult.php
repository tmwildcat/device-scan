<?php

namespace App\DeviceScan\Metadata;

class MetadataResult
{
    public function __construct(
        public readonly CandidateCollection $candidates,
        public readonly array $values = [],
        public readonly array $warnings = [],
        public readonly array $errors = [],
        public readonly ?int $pageCount = null,
        public readonly ?bool $isNativePdf = null,
        public readonly ?string $rawText = null,
        public readonly ?int $extractionTimeMs = null,
        public readonly ?string $extractorName = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'candidates' => $this->candidates->toArray(),
            'values' => $this->values,
            'warnings' => $this->warnings,
            'errors' => $this->errors,
            'page_count' => $this->pageCount,
            'is_native_pdf' => $this->isNativePdf,
            'raw_text' => $this->rawText,
            'extraction_time_ms' => $this->extractionTimeMs,
            'extractor_name' => $this->extractorName,
        ];
    }
}