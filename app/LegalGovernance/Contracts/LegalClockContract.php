<?php

namespace App\LegalGovernance\Contracts;

use DateTimeImmutable;

interface LegalClockContract
{
    public function now(): DateTimeImmutable;
}
