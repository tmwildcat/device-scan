<?php

namespace App\LegalGovernance\Contracts;

interface LegalAuditContract
{
    public function record(string $eventType, array $context): void;
}
