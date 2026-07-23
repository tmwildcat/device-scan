<?php

namespace App\LegalGovernance\Contracts;

interface LegalIdentityResolverContract
{
    /** @return array{type:string,id:string,name:string,organisation_type:?string,organisation_id:?string} */
    public function resolve(mixed $identity): array;
}
