<?php

namespace App\LegalGovernance\Contracts;

interface LegalOrganisationContract
{
    public function legalOrganisationType(): string;

    public function legalOrganisationId(): string;
}
