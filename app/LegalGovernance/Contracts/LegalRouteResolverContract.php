<?php

namespace App\LegalGovernance\Contracts;

interface LegalRouteResolverContract
{
    public function documentUrl(string $slug, ?string $version = null): string;
}
