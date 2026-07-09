<?php

namespace App\Http\Middleware;

use App\LineWatt\Access\EntitlementChecker;
use App\LineWatt\Access\LineWattRole;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $entitlements = app(EntitlementChecker::class);
        $locale = $user && Schema::hasColumn('users', 'preferred_locale')
            ? ($user->preferred_locale ?: 'en')
            : 'en';

        app()->setLocale($locale);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar ?? null,
                    'role' => $user->role,
                    'role_label' => LineWattRole::label($user->role),
                    'plan_code' => $user->plan_code,
                    'subscription_status' => $user->subscription_status,
                    'preferred_locale' => $locale,
                    'entitlements' => $entitlements->entitlementsFor($user),
                    'workspaces' => [
                        'central' => $entitlements->canAccessCentralLibrary($user),
                        'business' => $entitlements->canAccessBusinessAdmin($user),
                        'library' => $entitlements->canAccessLibraryAdmin($user),
                        'publisher' => $entitlements->canAccessPublisherWorkspace($user),
                        'my_library' => $entitlements->canAccessMyLibrary($user),
                        'partner' => $entitlements->canAccessPartnerPortal($user),
                        'manufacturer_admin' => $entitlements->canManageManufacturerAccount($user),
                        'platform' => $entitlements->canAccessPlatformAdmin($user),
                    ],
                ] : null,
            ],
            'locale' => $locale,
            'supported_locales' => config('linewatt-library.locales', ['en' => 'English']),
            'notifications' => $user && Schema::hasTable('notifications') ? [
                'unread_count' => Notification::query()
                    ->where('user_id', $user->id)
                    ->whereNull('read_at')
                    ->count(),
                'recent' => Notification::query()
                    ->where('user_id', $user->id)
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn (Notification $notification): array => [
                        'id' => $notification->id,
                        'uuid' => $notification->uuid,
                        'title' => $notification->title,
                        'body' => $notification->body,
                        'action_url' => $notification->action_url,
                        'read_at' => $notification->read_at?->toIso8601String(),
                    ])
                    ->all(),
            ] : ['unread_count' => 0, 'recent' => []],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
