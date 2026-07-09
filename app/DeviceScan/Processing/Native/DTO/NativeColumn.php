<?php

declare(strict_types=1);

namespace App\DeviceScan\Processing\Native\DTO;

final readonly class NativeColumn
{
    public function __construct(
        public int $index,
        public float $x,
        public float $width = 1.0,
        public array $metadata = [],
    ) {}
}