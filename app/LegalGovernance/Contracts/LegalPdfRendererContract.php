<?php

namespace App\LegalGovernance\Contracts;

interface LegalPdfRendererContract
{
    public function render(string $title, string $plainText): string;

    public function name(): string;

    public function version(): string;
}
