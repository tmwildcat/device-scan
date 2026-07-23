<?php

namespace App\LegalGovernance\Contracts;

interface LegalAuthenticationContextContract
{
    public function actor(): ?LegalActorContract;

    public function requestReference(): ?string;
}
