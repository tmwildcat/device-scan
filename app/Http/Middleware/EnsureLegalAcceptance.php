<?php

namespace App\Http\Middleware;

use App\LegalGovernance\Services\LegalAccessService;
use App\LegalGovernance\Services\LegalAuditService;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

final class EnsureLegalAcceptance
{
    public function __construct(private LegalAccessService $access, private LegalAuditService $audit) {}

    public function handle(Request $request, Closure $next, string $capability): Response
    {
        abort_unless($request->user() instanceof User, 401);
        $decision = $this->access->decisionForCapability($request->user(), $capability, ['source' => $request->route()?->getName()]);
        if ($decision->allowed) {
            return $next($request);
        }

        if (Cache::add('legal-access-blocked:'.hash('sha256', $request->user()->id.'|'.$capability.'|'.$decision->reasonCode), true, now()->addMinutes(5))) {
            $this->audit->record('legal_access_blocked', ['actor_type' => User::class, 'actor_id' => (string) $request->user()->id, 'summary' => 'Access blocked pending a legal action.', 'metadata' => ['capability' => $capability, 'workflow' => $decision->workflowKey, 'reason_code' => $decision->reasonCode]]);
        }
        if ($request->expectsJson()) {
            return response()->json(['message' => $decision->message, 'code' => $decision->configurationValid ? 'legal_acceptance_required' : 'legal_configuration_error', 'capability' => $capability, 'workflow' => $decision->workflowKey, 'blocking_obligations' => $decision->blockingObligations->count(), 'acceptance_url' => route('legal.acceptance.index', absolute: false)], 403);
        }

        if ($request->isMethod('GET')) {
            $destination = '/'.ltrim($request->getRequestUri(), '/');
            if (str_starts_with($destination, '/') && ! str_starts_with($destination, '//')) {
                $request->session()->put('legal.intended.'.$request->user()->id, $destination);
            }
        }
        $request->session()->put('legal.capability.'.$request->user()->id, $capability);

        return redirect()->route('legal.acceptance.index')->with('warning', $decision->message);
    }
}
