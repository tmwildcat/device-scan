<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters\Validation;

final readonly class InverterValidationIssue
{
    public function __construct(
        public string $severity,
        public string $code,
        public string $message,
        public ?string $modelName = null,
        public ?string $field = null,
        public string|float|int|bool|array|null $value = null,
        public ?array $context = null,
    ) {}

    public function toArray(): array
    {
        return [
            'severity' => $this->severity,
            'code' => $this->code,
            'message' => $this->message,
            'model_name' => $this->modelName,
            'field' => $this->field,
            'value' => $this->value,
            'context' => $this->context,
        ];
    }
}
