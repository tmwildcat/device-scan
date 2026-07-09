<?php

namespace App\Http\Middleware;

use App\LineWatt\Access\EntitlementChecker;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEntitlement
{
    public function __construct(private readonly EntitlementChecker $entitlements)
    {
    }

    public function handle(Request $request, Closure $next, string $entitlement): Response
    {
        abort_unless($this->entitlements->has($request->user(), $entitlement), 403);

        return $next($request);
    }
}
