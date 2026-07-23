<?php

namespace App\LegalGovernance\Contracts;

interface LegalNotificationContract
{
    public function notify(string $recipientReference, string $event, array $context = []): void;
}
