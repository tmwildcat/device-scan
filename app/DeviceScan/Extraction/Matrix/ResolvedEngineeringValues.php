<?php

namespace App\DeviceScan\Extraction\Matrix;

class ResolvedEngineeringValues
{
    public function __construct(
        public readonly string $model,

        public readonly string $condition,

        /**
         * Canonical engineering values.
         *
         * [
         *   'PNom' => 605,
         *   'Voc' => 55.10,
         *   ...
         * ]
         */
        public readonly array $values = [],

        /**
         * Original source row/cell information.
         */
        public readonly array $source = [],
    ) {
    }

    public function get(string $field): mixed
    {
        return $this->values[$field] ?? null;
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->values);
    }

    public function toArray(): array
    {
        return [
            'model' => $this->model,
            'condition' => $this->condition,
            'values' => $this->values,
            'source' => $this->source,
        ];
    }
}