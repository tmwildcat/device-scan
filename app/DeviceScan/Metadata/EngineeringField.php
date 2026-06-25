<?php

namespace App\DeviceScan\Metadata;

class EngineeringField
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $group,
        public readonly string $type = 'text',
        public readonly ?string $unit = null,
        public readonly bool $required = false,
        public readonly bool $editable = true,
        public readonly array $aliases = [],
        public readonly ?string $help = null,
        public readonly array $validation = [],
        public readonly array $export = [],
    ) {}

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'group' => $this->group,
            'type' => $this->type,
            'unit' => $this->unit,
            'required' => $this->required,
            'editable' => $this->editable,
            'aliases' => $this->aliases,
            'help' => $this->help,
            'validation' => $this->validation,
            'export' => $this->export,
        ];
    }
}