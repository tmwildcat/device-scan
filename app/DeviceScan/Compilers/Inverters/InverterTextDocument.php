<?php

declare(strict_types=1);

namespace App\DeviceScan\Compilers\Inverters;

final readonly class InverterTextDocument
{
    /**
     * @param array<int,string> $pages
     */
    public function __construct(
        public string $path,
        public string $filename,
        public array $pages,
    ) {}

    public function text(): string
    {
        return implode("\n\f\n", $this->pages);
    }
}
