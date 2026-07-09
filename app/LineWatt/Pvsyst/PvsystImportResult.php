<?php

namespace App\LineWatt\Pvsyst;

final readonly class PvsystImportResult
{
    public function __construct(
        public array $datasheet,
        public array $compiledRecord,
        public array $parsedFields,
        public array $warnings = [],
    ) {}
}
