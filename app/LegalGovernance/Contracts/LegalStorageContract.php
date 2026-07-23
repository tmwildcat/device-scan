<?php

namespace App\LegalGovernance\Contracts;

interface LegalStorageContract
{
    public function putImmutable(string $path, string $contents): void;

    public function get(string $path): string;
}
