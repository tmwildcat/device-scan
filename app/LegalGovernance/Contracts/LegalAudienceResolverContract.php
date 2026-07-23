<?php

namespace App\LegalGovernance\Contracts;

interface LegalAudienceResolverContract
{
    /** @return iterable<array{actor_type:string,actor_id:string,organisation_type:?string,organisation_id:?string}> */
    public function resolve(string $audience, array $context = []): iterable;
}
