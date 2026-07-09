<?php

namespace App\Http\Middleware;

use App\Models\InternalApplication;
use App\Models\InternalApplicationAccessLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInternalApplicationAccess
{
    public function handle(Request $request, Closure $next, ?string $scope = null): Response
    {
        $application = $this->resolveApplication($request);

        if (! $application instanceof InternalApplication) {
            $this->log($request, null, $scope, Response::HTTP_UNAUTHORIZED);

            abort(Response::HTTP_UNAUTHORIZED, 'Invalid internal application credentials.');
        }

        if ($application->status !== 'active' || $application->revoked_at !== null) {
            $this->log($request, $application, $scope, Response::HTTP_FORBIDDEN);

            abort(Response::HTTP_FORBIDDEN, 'Internal application access is not active.');
        }

        if (! $application->hasScope($scope)) {
            $this->log($request, $application, $scope, Response::HTTP_FORBIDDEN);

            abort(Response::HTTP_FORBIDDEN, 'Internal application scope is not allowed.');
        }

        $application->forceFill([
            'last_used_at' => now(),
            'last_used_ip' => $request->ip(),
        ])->save();

        $request->attributes->set('internal_application', $application);
        $request->attributes->set('internal_application_scope', $scope);

        $response = $next($request);

        $this->log($request, $application, $scope, $response->getStatusCode());

        return $response;
    }

    private function resolveApplication(Request $request): ?InternalApplication
    {
        $clientId = (string) $request->header('X-LineWatt-Client-Id', '');
        $secret = (string) $request->header('X-LineWatt-Client-Secret', '');

        if ($clientId === '' || $secret === '') {
            return null;
        }

        $application = InternalApplication::query()->where('client_id', $clientId)->first();

        if (! $application instanceof InternalApplication || ! $application->secretMatches($secret)) {
            return null;
        }

        return $application;
    }

    private function log(Request $request, ?InternalApplication $application, ?string $scope, ?int $statusCode): void
    {
        InternalApplicationAccessLog::query()->create([
            'internal_application_id' => $application?->id,
            'endpoint' => '/'.$request->path(),
            'method' => $request->method(),
            'scope_used' => $scope,
            'status_code' => $statusCode,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 2000),
        ]);
    }
}
