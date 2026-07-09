<?php

namespace App\LineWatt\Access;

use App\Models\User;

class EntitlementChecker
{
    public function has(?User $user, string $entitlement): bool
    {
        if ($this->overrideAllows($user, $entitlement) !== null) {
            return (bool) $this->overrideAllows($user, $entitlement);
        }

        return in_array($entitlement, $this->entitlementsFor($user), true);
    }

    /**
     * @return array<int,string>
     */
    public function entitlementsFor(?User $user): array
    {
        if (! $user instanceof User) {
            return [
                Entitlement::LIBRARY_SEARCH,
                Entitlement::LIBRARY_VIEW_RECORD,
            ];
        }

        $entitlements = config(
            "linewatt-access.role_entitlements.{$user->role}",
            config('linewatt-access.role_entitlements.'.LineWattRole::GUEST, [])
        );

        $planEntitlements = config("linewatt-access.plan_entitlements.{$user->plan_code}", []);
        foreach ($planEntitlements as $planEntitlement) {
            if (! in_array($planEntitlement, $entitlements, true)) {
                $entitlements[] = $planEntitlement;
            }
        }

        foreach (($user->entitlement_overrides ?? []) as $key => $allowed) {
            if ($allowed === true && ! in_array($key, $entitlements, true)) {
                $entitlements[] = $key;
            }

            if ($allowed === false) {
                $entitlements = array_values(array_diff($entitlements, [$key]));
            }
        }

        return $entitlements;
    }

    public function canAccessCentralLibrary(?User $user): bool
    {
        return $this->canAccessLibraryAdmin($user);
    }

    public function canAccessPlatformAdmin(?User $user): bool
    {
        return $user instanceof User
            && $user->role === LineWattRole::SUPER_ADMIN;
    }

    public function canAccessBusinessAdmin(?User $user): bool
    {
        return $user instanceof User
            && in_array($user->role, [LineWattRole::ADMIN, LineWattRole::SUPER_ADMIN], true);
    }

    public function canAccessLibraryAdmin(?User $user): bool
    {
        return $user instanceof User
            && in_array($user->role, [LineWattRole::LIBRARIAN, LineWattRole::ADMIN, LineWattRole::SUPER_ADMIN], true)
            && $this->has($user, Entitlement::CENTRAL_MANAGE);
    }

    public function canAccessMyLibrary(?User $user): bool
    {
        return $user instanceof User
            && $user->role === LineWattRole::SUBSCRIBER
            && $this->has($user, Entitlement::LIBRARY_PRIVATE_UPLOAD);
    }

    public function canAccessPublisherWorkspace(?User $user): bool
    {
        return $user instanceof User
            && $user->role === LineWattRole::LIBRARY_PUBLISHER
            && $this->has($user, Entitlement::LIBRARY_PUBLISHER_WORKFLOW);
    }

    public function canAccessPartnerPortal(?User $user): bool
    {
        return $user instanceof User
            && (
                in_array($user->role, LineWattRole::partnerRoles(), true)
                || in_array($user->role, [LineWattRole::SUPER_ADMIN, LineWattRole::ADMIN], true)
            )
            && $this->has($user, Entitlement::PARTNER_MANAGE_PRODUCTS);
    }

    public function canManageManufacturerAccount(?User $user): bool
    {
        return $user instanceof User
            && (
                $user->role === LineWattRole::PARTNER_ADMIN
                || in_array($user->role, [LineWattRole::SUPER_ADMIN, LineWattRole::ADMIN], true)
            );
    }

    public function landingPath(?User $user): string
    {
        if (! $user instanceof User) {
            return '/';
        }

        if ($this->canAccessPlatformAdmin($user) && $user->role === LineWattRole::SUPER_ADMIN) {
            return '/admin/platform';
        }

        if ($this->canAccessBusinessAdmin($user) && $user->role === LineWattRole::ADMIN) {
            return '/admin/business';
        }

        if ($this->canAccessLibraryAdmin($user)) {
            return '/admin/library';
        }

        if ($this->canAccessPublisherWorkspace($user)) {
            return '/publisher';
        }

        if ($user->role === LineWattRole::LIBRARY_CHAMPION) {
            return '/champion';
        }

        if ($this->canAccessMyLibrary($user)) {
            return '/my-library';
        }

        if ($this->canAccessPartnerPortal($user)) {
            return '/admin/manufacturer';
        }

        return '/';
    }

    private function overrideAllows(?User $user, string $entitlement): ?bool
    {
        if (! $user instanceof User) {
            return null;
        }

        $overrides = $user->entitlement_overrides ?? [];

        return array_key_exists($entitlement, $overrides) ? (bool) $overrides[$entitlement] : null;
    }
}
