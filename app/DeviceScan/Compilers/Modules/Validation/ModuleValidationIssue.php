<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Modules\Validation;

final readonly class ModuleValidationIssue
{
    public function __construct(
        public string $severity,
        public string $code,
        public string $message,
        public ?string $model = null,
        public ?string $field = null,
        public string|float|int|null $value = null,
        public array $context = [],
    ) {}

    public function toArray(): array
    {
        return [
            'severity' => $this->severity,
            'code' => $this->code,
            'message' => $this->message,
            'model' => $this->model,
            'field' => $this->field,
            'value' => $this->value,
            'context' => $this->context,
        ];
    }
}
