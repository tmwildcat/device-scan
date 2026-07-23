<?php

namespace App\LegalGovernance\Contracts;

interface LegalSubjectContract
{
    public function legalSubjectType(): string;

    public function legalSubjectId(): string;
}
