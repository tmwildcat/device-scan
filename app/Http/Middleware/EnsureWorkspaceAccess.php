<?php

namespace App\Http\Middleware;

use App\LineWatt\Access\EntitlementChecker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceAccess
{
    public function __construct(private readonly EntitlementChecker $entitlements)
    {
    }

    public function handle(Request $request, Closure $next, string $workspace): Response
    {
        $allowed = match ($workspace) {
            'central' => $this->entitlements->canAccessCentralLibrary($request->user()),
            'business' => $this->entitlements->canAccessBusinessAdmin($request->user()),
            'library' => $this->entitlements->canAccessLibraryAdmin($request->user()),
            'publisher' => $this->entitlements->canAccessPublisherWorkspace($request->user()),
            'my-library' => $this->entitlements->canAccessMyLibrary($request->user()),
            'partner' => $this->entitlements->canAccessPartnerPortal($request->user()),
            'manufacturer-admin' => $this->entitlements->canManageManufacturerAccount($request->user()),
            'platform' => $this->entitlements->canAccessPlatformAdmin($request->user()),
            default => false,
        };

        abort_unless($allowed, 403);

        return $next($request);
    }
}
