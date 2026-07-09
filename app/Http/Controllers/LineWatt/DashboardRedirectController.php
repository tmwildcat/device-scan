<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\EntitlementChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __construct(private readonly EntitlementChecker $entitlements)
    {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        return redirect($this->entitlements->landingPath($request->user()));
    }
}
