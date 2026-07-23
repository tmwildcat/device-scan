<?php

namespace App\LegalGovernance\Adapters;

use App\LegalGovernance\Contracts\LegalPdfRendererContract;
use App\LineWatt\Exports\SimplePdf;

final class LineWattLegalPdfRenderer implements LegalPdfRendererContract
{
    public function __construct(private SimplePdf $pdf) {}

    public function render(string $title, string $plainText): string
    {
        return $this->pdf->make([$title, '', ...preg_split('/\R/u', $plainText)]);
    }

    public function name(): string
    {
        return 'linewatt-simple-pdf';
    }

    public function version(): string
    {
        return '1';
    }
}
