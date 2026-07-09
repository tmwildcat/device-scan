<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\LineWattRole;
use App\Models\InternalApplicationAccessLog;
use App\Models\McpAuditLog;
use App\Models\PlatformService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PlatformServiceController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('LineWatt/PlatformServicesIndex', [
            'workspace' => $this->workspace($request),
            'services' => PlatformService::query()
                ->with('allowedInternalApplication:id,name,client_id')
                ->orderBy('service_type')
                ->orderBy('name')
                ->paginate(30)
                ->through(fn (PlatformService $service) => $this->serviceSummary($service)),
            'summary' => [
                'active' => PlatformService::query()->where('status', 'active')->count(),
                'paused' => PlatformService::query()->where('status', 'paused')->count(),
                'disabled' => PlatformService::query()->where('status', 'disabled')->count(),
                'maintenance' => PlatformService::query()->where('status', 'maintenance')->count(),
            ],
        ]);
    }

    public function show(Request $request, PlatformService $service): Response
    {
        $service->load('allowedInternalApplication:id,name,client_id');

        return Inertia::render('LineWatt/PlatformServiceShow', [
            'workspace' => $this->workspace($request),
            'service' => $this->serviceDetail($service),
            'recentActivity' => $this->recentActivity($service),
        ]);
    }

    public function pause(PlatformService $service): RedirectResponse
    {
        $service->update([
            'status' => $service->status === 'paused' ? 'active' : 'paused',
            'last_status_message' => $service->status === 'paused'
                ? 'Service marked active from the Super Admin service registry.'
                : 'Service marked paused in the Super Admin service registry. Runtime enforcement is not enabled yet.',
        ]);

        return redirect()->route('admin.platform.services.show', $service);
    }

    public function disable(PlatformService $service): RedirectResponse
    {
        $service->update([
            'status' => 'disabled',
            'last_status_message' => 'Service marked disabled in the Super Admin service registry. Runtime enforcement is not enabled yet.',
        ]);

        return redirect()->route('admin.platform.services.show', $service);
    }

    public function healthCheck(PlatformService $service): RedirectResponse
    {
        $service->update([
            'last_health_check_at' => now(),
            'last_status_message' => 'Health check placeholder recorded. No external health probe was executed.',
        ]);

        return redirect()->route('admin.platform.services.show', $service);
    }

    /**
     * @return array<string,mixed>
     */
    private function workspace(Request $request): array
    {
        return [
            'role_label' => LineWattRole::label($request->user()?->role),
            'environment' => app()->environment(),
            'health' => 'Healthy',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function serviceSummary(PlatformService $service): array
    {
        return [
            'uuid' => $service->uuid,
            'name' => $service->name,
            'service_key' => $service->service_key,
            'service_type' => $service->service_type,
            'status' => $service->status,
            'environment' => $service->environment,
            'required_scopes' => $service->required_scopes ?? [],
            'last_health_check_at' => optional($service->last_health_check_at)->toDateTimeString(),
            'href' => route('admin.platform.services.show', $service),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function serviceDetail(PlatformService $service): array
    {
        return [
            ...$this->serviceSummary($service),
            'description' => $service->description,
            'endpoint_url' => $service->endpoint_url,
            'health_check_url' => $service->health_check_url,
            'last_status_message' => $service->last_status_message,
            'metadata' => $service->metadata ?? [],
            'linked_internal_app' => $service->allowedInternalApplication ? [
                'name' => $service->allowedInternalApplication->name,
                'client_id' => $service->allowedInternalApplication->client_id,
            ] : null,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function recentActivity(PlatformService $service): array
    {
        if ($service->service_type === 'mcp_gateway') {
            return McpAuditLog::query()
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (McpAuditLog $log) => [
                    'label' => $log->tool_name ?: $log->action,
                    'detail' => $log->status,
                    'status_code' => $log->status_code,
                    'created_at' => optional($log->created_at)->toDateTimeString(),
                ])
                ->all();
        }

        if ($service->service_type === 'internal_api') {
            return InternalApplicationAccessLog::query()
                ->latest('created_at')
                ->limit(8)
                ->get()
                ->map(fn (InternalApplicationAccessLog $log) => [
                    'label' => $log->method.' '.$log->endpoint,
                    'detail' => $log->scope_used ?: 'no scope',
                    'status_code' => $log->status_code,
                    'created_at' => (string) $log->created_at,
                ])
                ->all();
        }

        return [
            [
                'label' => 'Activity placeholder',
                'detail' => 'Service-specific activity history will be connected in a later operations milestone.',
                'status_code' => null,
                'created_at' => null,
            ],
        ];
    }
}
