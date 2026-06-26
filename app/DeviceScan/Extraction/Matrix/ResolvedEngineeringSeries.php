<?php

namespace App\DeviceScan\Extraction\Matrix;

class ResolvedEngineeringSeries
{
    /**
     * @var ResolvedEngineeringValues[]
     */
    protected array $items = [];

    public function add(ResolvedEngineeringValues $values): self
    {
        $this->items[] = $values;

        return $this;
    }

    /**
     * @return ResolvedEngineeringValues[]
     */
    public function all(): array
    {
        return $this->items;
    }

    public function first(): ?ResolvedEngineeringValues
    {
        return $this->items[0] ?? null;
    }

    public function find(string $model, string $condition): ?ResolvedEngineeringValues
    {
        foreach ($this->items as $item) {
            if (
                $item->model === $model &&
                $item->condition === $condition
            ) {
                return $item;
            }
        }

        return null;
    }

    public function toArray(): array
    {
        return array_map(
            fn (ResolvedEngineeringValues $item) => $item->toArray(),
            $this->items
        );
    }
}