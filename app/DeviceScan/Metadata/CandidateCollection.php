<?php

namespace App\DeviceScan\Metadata;

use Countable;
use IteratorAggregate;
use ArrayIterator;
use Traversable;

class CandidateCollection implements Countable, IteratorAggregate
{
    /**
     * @var CandidateValue[]
     */
    protected array $items = [];

    /**
     * @param CandidateValue[] $items
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }

    public function add(CandidateValue $candidate): self
    {
        $this->items[] = $candidate;

        return $this;
    }

    /**
     * @return CandidateValue[]
     */
    public function all(): array
    {
        return $this->items;
    }

    public function first(): ?CandidateValue
    {
        return $this->items[0] ?? null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * All candidates for one engineering field.
     */
    public function forField(string $field): self
    {
        return new self(
            array_values(
                array_filter(
                    $this->items,
                    fn (CandidateValue $candidate) => $candidate->field === $field
                )
            )
        );
    }

    /**
     * Highest confidence candidate for a field.
     */
    public function best(string $field): ?CandidateValue
    {
        $items = $this->forField($field)->all();

        usort(
            $items,
            fn (CandidateValue $a, CandidateValue $b) =>
                ($b->confidence ?? 0) <=> ($a->confidence ?? 0)
        );

        return $items[0] ?? null;
    }

    /**
     * Invalid engineering values.
     */
    public function invalid(): self
    {
        return new self(
            array_values(
                array_filter(
                    $this->items,
                    fn (CandidateValue $candidate) => ! $candidate->isValid
                )
            )
        );
    }

    /**
     * Valid engineering values.
     */
    public function valid(): self
    {
        return new self(
            array_values(
                array_filter(
                    $this->items,
                    fn (CandidateValue $candidate) => $candidate->isValid
                )
            )
        );
    }

    /**
     * Candidates found on one page.
     */
    public function page(int $page): self
    {
        return new self(
            array_values(
                array_filter(
                    $this->items,
                    fn (CandidateValue $candidate) => $candidate->page === $page
                )
            )
        );
    }

    /**
     * Export.
     */
    public function toArray(): array
    {
        return array_map(
            fn (CandidateValue $candidate) => $candidate->toArray(),
            $this->items
        );
    }
}