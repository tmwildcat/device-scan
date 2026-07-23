<?php

namespace App\LegalGovernance\Adapters;

use App\LegalGovernance\Contracts\LegalIdentityResolverContract;
use App\Models\User;
use InvalidArgumentException;

final class LineWattLegalIdentityResolver implements LegalIdentityResolverContract
{
    public function resolve(mixed $identity): array
    {
        if (! $identity instanceof User) {
            throw new InvalidArgumentException('Unsupported legal identity.');
        }

        return ['type' => User::class, 'id' => (string) $identity->getKey(), 'name' => $identity->name, 'organisation_type' => $identity->manufacturer_company_id ? 'manufacturer_company' : null, 'organisation_id' => $identity->manufacturer_company_id ? (string) $identity->manufacturer_company_id : null];
    }
}
