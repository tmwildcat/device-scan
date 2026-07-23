<?php

namespace App\LegalGovernance\Contracts;

interface LegalActorContract
{
    public function legalActorType(): string;

    public function legalActorId(): string;

    public function legalActorDisplayName(): string;
}
