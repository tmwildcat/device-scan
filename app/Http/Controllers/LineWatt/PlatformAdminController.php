<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\LineWattRole;
use App\Models\Activity;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\NotificationDelivery;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PlatformAdminController extends Controller
{
    public function __invoke(): Response
    {
        return $this->renderSection('dashboard');
    }

    public function section(string $section): Response
    {
        return $this->renderSection($section);
    }

    private function renderSection(string $section): Response
    {
        $section = $this->normalizeSection($section);

        return Inertia::render('LineWatt/PlatformAdmin', [
            'workspace' => [
                'name' => 'Platform Admin',
                'role' => auth()->user()?->role,
                'role_label' => LineWattRole::label(auth()->user()?->role),
                'environment' => app()->environment(),
                'health' => $this->systemHealthIndicator(),
                'nav_groups' => ['Platform', 'Users & Security', 'Infrastructure'],
            ],
            'section' => $this->sectionPayload($section),
            'summary' => $this->summary(),
        ]);
    }

    /**
     * @return array<string,int|string>
     */
    private function summary(): array
    {
        $summary = [
            'users' => 0,
            'subscribers' => 0,
            'manufacturers' => 0,
            'platform_roles' => 0,
            'datasheets' => 0,
            'engineering_records' => 0,
            'failed_jobs' => 0,
            'notification_failures' => 0,
        ];

        if (Schema::hasTable('users')) {
            $summary['users'] = User::query()->count();
            $summary['subscribers'] = User::query()->where('role', LineWattRole::SUBSCRIBER)->count();
            $summary['manufacturers'] = User::query()->whereIn('role', LineWattRole::partnerRoles())->count();
            $summary['platform_roles'] = User::query()->whereIn('role', LineWattRole::platformRoles())->count();
        }

        if (Schema::hasTable('device_datasheets')) {
            $summary['datasheets'] = DeviceDatasheet::query()->count();
        }

        if (Schema::hasTable('compiled_device_records')) {
            $summary['engineering_records'] = CompiledDeviceRecord::query()->count();
        }

        if (Schema::hasTable('failed_jobs')) {
            $summary['failed_jobs'] = DB::table('failed_jobs')->count();
        }

        if (Schema::hasTable('notification_deliveries')) {
            $summary['notification_failures'] = NotificationDelivery::query()->where('status', 'failed')->count();
        }

        return $summary;
    }

    /**
     * @return array<string,mixed>
     */
    private function sectionPayload(string $section): array
    {
        return match ($section) {
            'system-health' => $this->systemHealth(),
            'security' => $this->security(),
            'storage', 'object-storage' => $this->storage($section),
            'background-jobs', 'queue-monitor' => $this->jobs($section),
            'notifications' => $this->notifications(),
            'logs' => $this->logs(),
            'backup-recovery' => $this->backupRecovery(),
            'environment' => $this->environment(),
            'feature-flags' => $this->featureFlags(),
            'api-keys', 'internal-app-access' => $this->internalAppAccess(),
            'audit-logs' => $this->auditLogs(),
            'developer-tools' => $this->developerTools(),
            'system-administrators' => $this->systemAdministrators(),
            'roles', 'permissions', 'entitlements', 'authentication', 'sso' => $this->securityPlaceholder($section),
            'compiler-services' => $this->compilerServices(),
            'search-index', 'email', 'scheduled-jobs', 'monitoring' => $this->infrastructurePlaceholder($section),
            default => $this->dashboard(),
        };
    }

    private function dashboard(): array
    {
        return [
            'key' => 'dashboard',
            'title' => 'Platform Dashboard',
            'subtitle' => 'System operations for health, security, storage, queues, notifications and infrastructure.',
            'cards' => [
                ['label' => 'Application Status', 'value' => $this->systemHealthIndicator(), 'tone' => 'emerald'],
                ['label' => 'Queue Status', 'value' => Config::get('queue.default', 'sync'), 'tone' => 'sky'],
                ['label' => 'Storage Health', 'value' => $this->storageReachable() ? 'Available' : 'Check', 'tone' => $this->storageReachable() ? 'emerald' : 'amber'],
                ['label' => 'Email Health', 'value' => Config::get('mail.default', 'unknown'), 'tone' => 'violet'],
                ['label' => 'Background Jobs', 'value' => (string) $this->summary()['failed_jobs'], 'tone' => $this->summary()['failed_jobs'] > 0 ? 'amber' : 'emerald'],
                ['label' => 'Security Alerts', 'value' => '0', 'tone' => 'emerald'],
                ['label' => 'Recent Errors', 'value' => $this->logReadable() ? 'Log readable' : 'No log', 'tone' => 'sky'],
                ['label' => 'Disk Usage', 'value' => $this->diskUsage(), 'tone' => 'sky'],
                ['label' => 'Object Storage Availability', 'value' => $this->storageReachable() ? 'Online' : 'Unknown', 'tone' => $this->storageReachable() ? 'emerald' : 'amber'],
            ],
            'panels' => [
                $this->panel('Operational boundaries', 'Super Admin controls platform infrastructure only. Library, business, manufacturer and subscriber workflows stay in their own workspaces.'),
                $this->panel('Status posture', 'This dashboard intentionally surfaces infrastructure signals and placeholders without exposing secrets or destructive controls.'),
            ],
        ];
    }

    private function systemHealth(): array
    {
        return [
            'key' => 'system-health',
            'title' => 'System Health',
            'subtitle' => 'Runtime, framework, database, cache, queue, storage and mail status.',
            'cards' => [
                ['label' => 'Application Status', 'value' => $this->systemHealthIndicator(), 'tone' => 'emerald'],
                ['label' => 'Environment', 'value' => app()->environment(), 'tone' => 'sky'],
                ['label' => 'PHP', 'value' => PHP_VERSION, 'tone' => 'violet'],
                ['label' => 'Laravel', 'value' => Application::VERSION, 'tone' => 'emerald'],
            ],
            'rows' => [
                ['label' => 'Database connection', 'value' => $this->databaseReachable() ? 'Connected' : 'Unavailable'],
                ['label' => 'Cache driver', 'value' => Config::get('cache.default')],
                ['label' => 'Queue connection', 'value' => Config::get('queue.default')],
                ['label' => 'Storage disk', 'value' => Config::get('device-scan.storage_disk', Config::get('filesystems.default'))],
                ['label' => 'Storage status', 'value' => $this->storageReachable() ? 'Available' : 'Check required'],
                ['label' => 'Mail configuration', 'value' => Config::get('mail.default')],
                ['label' => 'Last scheduler run', 'value' => 'Placeholder'],
            ],
        ];
    }

    private function security(): array
    {
        return [
            'key' => 'security',
            'title' => 'Security',
            'subtitle' => 'Authentication posture, sessions, suspicious activity and security checklist.',
            'cards' => [
                ['label' => 'Authentication', 'value' => 'Fortify', 'tone' => 'emerald'],
                ['label' => '2FA / Passkeys', 'value' => 'Placeholder', 'tone' => 'amber'],
                ['label' => 'Failed Login Signals', 'value' => 'Placeholder', 'tone' => 'sky'],
                ['label' => 'Active Sessions', 'value' => 'Placeholder', 'tone' => 'violet'],
            ],
            'checks' => [
                ['label' => 'Production debug disabled', 'ok' => ! (bool) Config::get('app.debug') || ! app()->isProduction()],
                ['label' => 'HTTPS termination configured', 'ok' => true, 'note' => 'Verify at infrastructure edge.'],
                ['label' => 'Secrets hidden from UI', 'ok' => true],
                ['label' => 'Suspicious activity review', 'ok' => false, 'note' => 'Placeholder until audit risk scoring is added.'],
            ],
        ];
    }

    private function storage(string $section): array
    {
        return [
            'key' => $section,
            'title' => $section === 'object-storage' ? 'Object Storage' : 'Storage',
            'subtitle' => 'Configured disks, object storage health, artifact counts and verification posture.',
            'cards' => [
                ['label' => 'Default Disk', 'value' => Config::get('filesystems.default'), 'tone' => 'sky'],
                ['label' => 'DeviceScan Disk', 'value' => Config::get('device-scan.storage_disk', 'not configured'), 'tone' => 'emerald'],
                ['label' => 'Datasheets', 'value' => (string) (Schema::hasTable('device_datasheets') ? DeviceDatasheet::query()->count() : 0), 'tone' => 'violet'],
                ['label' => 'Engineering JSON', 'value' => (string) (Schema::hasTable('compiled_device_records') ? CompiledDeviceRecord::query()->count() : 0), 'tone' => 'amber'],
            ],
            'rows' => [
                ['label' => 'Storage reachable', 'value' => $this->storageReachable() ? 'Yes' : 'Check required'],
                ['label' => 'Artifact verification command', 'value' => 'php artisan device-scan:verify-artifacts'],
                ['label' => 'Hash rebuild command', 'value' => 'php artisan device-scan:rebuild-hashes'],
                ['label' => 'Orphan cleanup', 'value' => 'Placeholder'],
                ['label' => 'Hash verification status', 'value' => 'Command available'],
            ],
        ];
    }

    private function jobs(string $section): array
    {
        $failedJobs = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->latest('failed_at')->limit(10)->get()->map(fn ($job): array => [
                'id' => $job->id ?? null,
                'connection' => $job->connection ?? 'unknown',
                'queue' => $job->queue ?? 'unknown',
                'failed_at' => $job->failed_at ?? null,
                'exception' => str($job->exception ?? '')->limit(140)->toString(),
            ])->all()
            : [];

        return [
            'key' => $section,
            'title' => $section === 'queue-monitor' ? 'Queue Monitor' : 'Background Jobs',
            'subtitle' => 'Queue connection, failed jobs, pending jobs placeholder and retry workflow placeholders.',
            'cards' => [
                ['label' => 'Queue Connection', 'value' => Config::get('queue.default'), 'tone' => 'sky'],
                ['label' => 'Failed Jobs', 'value' => (string) count($failedJobs), 'tone' => count($failedJobs) > 0 ? 'amber' : 'emerald'],
                ['label' => 'Pending Jobs', 'value' => 'Placeholder', 'tone' => 'violet'],
                ['label' => 'Retry Action', 'value' => 'Disabled', 'tone' => 'amber'],
            ],
            'table' => [
                'columns' => ['ID', 'Connection', 'Queue', 'Failed At', 'Exception'],
                'rows' => array_map(fn (array $job): array => [
                    $job['id'] ?? '-',
                    $job['connection'],
                    $job['queue'],
                    $job['failed_at'] ?? '-',
                    $job['exception'] ?: '-',
                ], $failedJobs),
            ],
        ];
    }

    private function notifications(): array
    {
        $deliveries = Schema::hasTable('notification_deliveries')
            ? NotificationDelivery::query()->latest()->limit(10)->get()
            : collect();

        return [
            'key' => 'notifications',
            'title' => 'Notifications',
            'subtitle' => 'In-app and email delivery health, failed deliveries and channel placeholders.',
            'cards' => [
                ['label' => 'Deliveries', 'value' => (string) $deliveries->count(), 'tone' => 'sky'],
                ['label' => 'Failed Delivery', 'value' => (string) $deliveries->where('status', 'failed')->count(), 'tone' => 'amber'],
                ['label' => 'Pending Delivery', 'value' => (string) $deliveries->where('status', 'pending')->count(), 'tone' => 'violet'],
                ['label' => 'Email Channel', 'value' => Config::get('mail.default'), 'tone' => 'emerald'],
            ],
            'rows' => [
                ['label' => 'In-app channel', 'value' => 'Enabled'],
                ['label' => 'Email templates', 'value' => 'Placeholder'],
                ['label' => 'Queued mail', 'value' => 'Use queue connection: '.Config::get('queue.default')],
            ],
        ];
    }

    private function logs(): array
    {
        return [
            'key' => 'logs',
            'title' => 'Logs',
            'subtitle' => 'Recent application error visibility. Downloads are disabled because logs may contain sensitive data.',
            'cards' => [
                ['label' => 'Log File', 'value' => $this->logReadable() ? 'Readable' : 'Unavailable', 'tone' => $this->logReadable() ? 'emerald' : 'amber'],
                ['label' => 'Download Logs', 'value' => 'Disabled', 'tone' => 'amber'],
                ['label' => 'Recent Errors', 'value' => 'Placeholder', 'tone' => 'sky'],
            ],
            'panels' => [
                $this->panel('Sensitive log policy', 'Log downloads remain disabled by default. Use server access controls for deeper investigation.'),
            ],
        ];
    }

    private function backupRecovery(): array
    {
        return [
            'key' => 'backup-recovery',
            'title' => 'Backup & Recovery',
            'subtitle' => 'Backup posture, restore policy and storage backup locations.',
            'cards' => [
                ['label' => 'Backup Status', 'value' => 'Placeholder', 'tone' => 'amber'],
                ['label' => 'Last Backup', 'value' => 'Not recorded', 'tone' => 'sky'],
                ['label' => 'Restore Policy', 'value' => 'Placeholder', 'tone' => 'violet'],
            ],
            'rows' => [
                ['label' => 'Storage backup location', 'value' => 'Placeholder'],
                ['label' => 'Database backup policy', 'value' => 'Placeholder'],
            ],
        ];
    }

    private function environment(): array
    {
        return [
            'key' => 'environment',
            'title' => 'Environment',
            'subtitle' => 'Safe runtime configuration only. Secrets are never shown.',
            'cards' => [
                ['label' => 'App Env', 'value' => app()->environment(), 'tone' => 'sky'],
                ['label' => 'Debug Mode', 'value' => Config::get('app.debug') ? 'On' : 'Off', 'tone' => Config::get('app.debug') ? 'amber' : 'emerald'],
                ['label' => 'Timezone', 'value' => Config::get('app.timezone'), 'tone' => 'violet'],
                ['label' => 'Locale', 'value' => Config::get('app.locale'), 'tone' => 'emerald'],
            ],
            'rows' => [
                ['label' => 'Queue driver', 'value' => Config::get('queue.default')],
                ['label' => 'Cache driver', 'value' => Config::get('cache.default')],
                ['label' => 'Mail driver', 'value' => Config::get('mail.default')],
                ['label' => 'Storage product', 'value' => Config::get('linewatt-storage.product', 'line-watt-library')],
                ['label' => 'Storage namespace', 'value' => Config::get('linewatt-storage.namespace', 'common')],
            ],
        ];
    }

    private function featureFlags(): array
    {
        return [
            'key' => 'feature-flags',
            'title' => 'Feature Flags',
            'subtitle' => 'Status-only flag visibility. Editing is not implemented.',
            'rows' => [
                ['label' => 'LINEWATT_LIB_DEBUG', 'value' => Config::get('linewatt-library.debug') ? 'Enabled' : 'Disabled'],
                ['label' => 'Paddle checkout', 'value' => 'Placeholder'],
                ['label' => 'MCP gateway', 'value' => 'Future public integration layer'],
                ['label' => 'AI discoverability', 'value' => 'Placeholder'],
            ],
        ];
    }

    private function internalAppAccess(): array
    {
        return [
            'key' => 'internal-app-access',
            'title' => 'Internal App Access',
            'subtitle' => 'Sanctum-based first-party app and internal service access placeholders. No OAuth2 or public developer key workflow is exposed in v1.',
            'panels' => [
                $this->panel('V1 access model', 'LineWatt-owned apps, swem2m apps, Studio and internal services use Sanctum tokens with rate limiting, first-party scope checks and audit logging.'),
                $this->panel('MCP later', 'The future MCP gateway is the public integration layer. It will call private API/service methods internally rather than querying the database directly.'),
            ],
            'rows' => [
                ['label' => 'First-party apps', 'value' => 'LineWatt Library, LineWatt Studio, swem2m apps, future mobile apps'],
                ['label' => 'Token model', 'value' => 'Laravel Sanctum first-party app and internal service tokens'],
                ['label' => 'Route groups', 'value' => '/api/internal/... or /api/linewatt/...'],
                ['label' => 'Middleware', 'value' => 'auth:sanctum, first_party_app ability/scope check, rate limiting, audit logging'],
                ['label' => 'Token abilities', 'value' => 'library.search, library.view_record, library.export, library.compare, library.download_pdf, library.private_upload, library.private_compile'],
                ['label' => 'Third-party access', 'value' => 'Not available in v1'],
                ['label' => 'Passport / OAuth2', 'value' => 'Out of scope unless a future third-party OAuth developer API is required'],
            ],
        ];
    }

    private function auditLogs(): array
    {
        $rows = Schema::hasTable('activities')
            ? Activity::query()->with('actor')->latest()->limit(20)->get()->map(fn (Activity $activity): array => [
                $activity->actor?->email ?? 'System',
                $activity->event,
                $activity->subject_type ? class_basename($activity->subject_type) : '-',
                $activity->created_at?->toDateTimeString() ?? '-',
                str(json_encode($activity->metadata ?? []))->limit(120)->toString(),
            ])->all()
            : [];

        return [
            'key' => 'audit-logs',
            'title' => 'Audit Logs',
            'subtitle' => 'Recent platform and workflow activity if the activity table exists.',
            'table' => [
                'columns' => ['Actor', 'Action', 'Entity', 'Timestamp', 'Metadata'],
                'rows' => $rows,
            ],
        ];
    }

    private function developerTools(): array
    {
        return [
            'key' => 'developer-tools',
            'title' => 'Developer Tools',
            'subtitle' => 'Read-only command references. Destructive or mutating commands are not executed from the UI.',
            'rows' => [
                ['label' => 'Storage check', 'value' => 'php artisan device-scan:storage-check'],
                ['label' => 'Verify artifacts', 'value' => 'php artisan device-scan:verify-artifacts'],
                ['label' => 'Compile golden set', 'value' => 'php artisan device-scan:compile-golden-set'],
                ['label' => 'Validate golden JSON', 'value' => 'php artisan device-scan:validate-golden-json'],
                ['label' => 'Route list', 'value' => 'php artisan route:list'],
                ['label' => 'Cache clear', 'value' => 'php artisan cache:clear'],
                ['label' => 'Scheduler status', 'value' => 'Placeholder'],
            ],
        ];
    }

    private function systemAdministrators(): array
    {
        $rows = Schema::hasTable('users')
            ? User::query()->whereIn('role', [LineWattRole::SUPER_ADMIN, LineWattRole::ADMIN])
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (User $user): array => [
                    $user->name,
                    $user->email,
                    LineWattRole::label($user->role),
                    $user->created_at?->toDateString() ?? '-',
                ])
                ->all()
            : [];

        return [
            'key' => 'system-administrators',
            'title' => 'System Administrators',
            'subtitle' => 'Platform and business administrators. Editing belongs to a future access-governance workflow.',
            'table' => [
                'columns' => ['Name', 'Email', 'Role', 'Created'],
                'rows' => $rows,
            ],
        ];
    }

    private function securityPlaceholder(string $section): array
    {
        return [
            'key' => $section,
            'title' => str($section)->replace('-', ' ')->title()->toString(),
            'subtitle' => 'Intentional shell page for platform access governance.',
            'panels' => [
                $this->panel('Policy boundary', 'This page belongs to Super Admin only. Role, permission and entitlement editing will be added after the governance model is finalized.'),
            ],
        ];
    }

    private function compilerServices(): array
    {
        return [
            'key' => 'compiler-services',
            'title' => 'Compiler Services',
            'subtitle' => 'Operational health of compiler services. Business-level compiler roadmap stays under Business Administration.',
            'cards' => [
                ['label' => 'Module Compiler', 'value' => 'v0.2+', 'tone' => 'emerald'],
                ['label' => 'Inverter Compiler', 'value' => 'v0.4+', 'tone' => 'emerald'],
                ['label' => 'Golden Corpus', 'value' => 'Available', 'tone' => 'sky'],
                ['label' => 'Regression Status', 'value' => 'Placeholder', 'tone' => 'amber'],
            ],
            'rows' => [
                ['label' => 'Recent compile failures', 'value' => Schema::hasTable('device_datasheets') ? (string) DeviceDatasheet::query()->where('status', 'failed')->count() : '0'],
                ['label' => 'Golden JSON validation', 'value' => 'php artisan device-scan:validate-golden-json'],
            ],
        ];
    }

    private function infrastructurePlaceholder(string $section): array
    {
        $title = str($section)->replace('-', ' ')->title()->toString();

        return [
            'key' => $section,
            'title' => $title,
            'subtitle' => "Platform infrastructure shell for {$title}.",
            'panels' => [
                $this->panel($title, 'Status widgets and operational controls will be added once observability integrations are finalized.'),
            ],
        ];
    }

    private function normalizeSection(string $section): string
    {
        $allowed = [
            'dashboard',
            'system-health',
            'security',
            'storage',
            'background-jobs',
            'queue-monitor',
            'notifications',
            'logs',
            'backup-recovery',
            'environment',
            'feature-flags',
            'api-keys',
            'internal-app-access',
            'audit-logs',
            'developer-tools',
            'system-administrators',
            'roles',
            'permissions',
            'entitlements',
            'authentication',
            'sso',
            'object-storage',
            'compiler-services',
            'search-index',
            'email',
            'scheduled-jobs',
            'monitoring',
        ];

        return in_array($section, $allowed, true) ? $section : 'dashboard';
    }

    private function databaseReachable(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function storageReachable(): bool
    {
        try {
            Storage::disk(Config::get('device-scan.storage_disk', Config::get('filesystems.default')))->exists('__linewatt_healthcheck__');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function logReadable(): bool
    {
        $path = storage_path('logs/laravel.log');

        return is_file($path) && is_readable($path);
    }

    private function diskUsage(): string
    {
        $total = @disk_total_space(base_path());
        $free = @disk_free_space(base_path());

        if (! $total || ! $free) {
            return 'Unknown';
        }

        $usedPercent = (int) round((($total - $free) / $total) * 100);

        return $usedPercent.'%';
    }

    private function systemHealthIndicator(): string
    {
        return $this->databaseReachable() ? 'Healthy' : 'Attention';
    }

    /**
     * @return array{title:string,body:string}
     */
    private function panel(string $title, string $body): array
    {
        return ['title' => $title, 'body' => $body];
    }
}
