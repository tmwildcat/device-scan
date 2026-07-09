<?php

namespace App\Http\Responses;

use App\LineWatt\Access\EntitlementChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse;

class LineWattLoginResponse implements LoginResponse
{
    public function __construct(private readonly EntitlementChecker $entitlements)
    {
    }

    public function toResponse($request): JsonResponse|RedirectResponse
    {
        /** @var Request $request */
        $path = $this->entitlements->landingPath($request->user());

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false])
            : redirect()->intended($path);
    }
}
