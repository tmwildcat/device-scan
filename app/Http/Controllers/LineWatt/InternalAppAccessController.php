<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\LineWattRole;
use App\Models\InternalApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InternalAppAccessController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('LineWatt/InternalAppAccessIndex', [
            'workspace' => $this->workspace($request),
            'applications' => InternalApplication::query()
                ->with('creator:id,name,email')
                ->latest()
                ->paginate(20)
                ->through(fn (InternalApplication $application) => $this->applicationSummary($application)),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('LineWatt/InternalAppAccessCreate', [
            'workspace' => $this->workspace($request),
            'scopes' => InternalApplication::SCOPES,
            'environments' => ['local', 'staging', 'production'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedPayload($request);
        $secret = InternalApplication::generateSecret();

        $application = new InternalApplication($data);
        $application->created_by = $request->user()?->id;
        $application->setPlainSecret($secret);
        $application->save();

        return redirect()->route('admin.platform.internal-app-access.show', $application)
            ->with('internal_app_secret', $secret);
    }

    public function show(Request $request, InternalApplication $application): Response
    {
        $application->load('creator:id,name,email');

        return Inertia::render('LineWatt/InternalAppAccessShow', [
            'workspace' => $this->workspace($request),
            'application' => $this->applicationDetail($application),
            'scopes' => InternalApplication::SCOPES,
            'environments' => ['local', 'staging', 'production'],
            'oneTimeSecret' => $request->session()->pull('internal_app_secret'),
            'recentLogs' => $application->accessLogs()
                ->latest('created_at')
                ->limit(10)
                ->get()
                ->map(fn ($log) => [
                    'endpoint' => $log->endpoint,
                    'method' => $log->method,
                    'scope_used' => $log->scope_used,
                    'status_code' => $log->status_code,
                    'ip' => $log->ip,
                    'created_at' => (string) $log->created_at,
                ]),
        ]);
    }

    public function update(Request $request, InternalApplication $application): RedirectResponse
    {
        $application->update($this->validatedPayload($request, true));

        return redirect()->route('admin.platform.internal-app-access.show', $application)
            ->with('status', 'Internal application updated.');
    }

    public function pause(Request $request, InternalApplication $application): RedirectResponse
    {
        $application->update([
            'status' => $application->status === 'paused' ? 'active' : 'paused',
        ]);

        return redirect()->route('admin.platform.internal-app-access.show', $application);
    }

    public function revoke(Request $request, InternalApplication $application): RedirectResponse
    {
        $application->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        return redirect()->route('admin.platform.internal-app-access.show', $application);
    }

    public function regenerateSecret(Request $request, InternalApplication $application): RedirectResponse
    {
        $secret = InternalApplication::generateSecret();
        $application->setPlainSecret($secret);
        $application->save();

        return redirect()->route('admin.platform.internal-app-access.show', $application)
            ->with('internal_app_secret', $secret);
    }

    /**
     * @return array<string,mixed>
     */
    private function validatedPayload(Request $request, bool $isUpdate = false): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'environment' => ['required', Rule::in(['local', 'staging', 'production'])],
            'allowed_domains' => ['nullable'],
            'scopes' => ['nullable', 'array'],
            'scopes.*' => [Rule::in(InternalApplication::SCOPES)],
            'status' => [$isUpdate ? 'required' : 'nullable', Rule::in(['active', 'paused', 'revoked'])],
        ]);

        $domains = $data['allowed_domains'] ?? [];
        if (is_string($domains)) {
            $domains = preg_split('/\r\n|\r|\n|,/', $domains) ?: [];
        }

        $data['allowed_domains'] = collect($domains)
            ->map(fn ($domain) => trim((string) $domain))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $data['scopes'] = array_values(array_unique($data['scopes'] ?? []));

        if (! $isUpdate) {
            unset($data['status']);
        }

        if ($isUpdate && Arr::get($data, 'status') !== 'revoked') {
            $data['revoked_at'] = null;
        }

        return $data;
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
    private function applicationSummary(InternalApplication $application): array
    {
        return [
            'uuid' => $application->uuid,
            'name' => $application->name,
            'client_id' => $application->client_id,
            'environment' => $application->environment,
            'allowed_domains' => $application->allowed_domains ?? [],
            'scopes' => $application->scopes ?? [],
            'status' => $application->status,
            'last_used_at' => optional($application->last_used_at)->toDateTimeString(),
            'last_used_ip' => $application->last_used_ip,
            'href' => route('admin.platform.internal-app-access.show', $application),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function applicationDetail(InternalApplication $application): array
    {
        return [
            ...$this->applicationSummary($application),
            'description' => $application->description,
            'created_by' => $application->creator?->name,
            'created_at' => optional($application->created_at)->toDateTimeString(),
            'revoked_at' => optional($application->revoked_at)->toDateTimeString(),
        ];
    }
}
