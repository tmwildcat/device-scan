<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\LineWattRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;

class ManufacturerUserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('legal.acceptance:manufacturer.portal.access')];
    }

    public function index(Request $request): Response
    {
        $user = $request->user();
        $company = $user?->manufacturerCompany;
        $isPlatformOperator = $user && in_array($user->role, [LineWattRole::SUPER_ADMIN, LineWattRole::ADMIN], true);
        $canManageUsers = $isPlatformOperator || $user?->manufacturer_role === 'manufacturer_admin';

        $users = User::query()
            ->when($company, fn ($query) => $query->where('manufacturer_company_id', $company->id))
            ->when(! $company && ! $isPlatformOperator, fn ($query) => $query->whereRaw('1 = 0'))
            ->when(! $company && $isPlatformOperator, fn ($query) => $query->whereNotNull('manufacturer_company_id'))
            ->orderBy('name')
            ->get()
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->manufacturer_role,
                'role_label' => match ($member->manufacturer_role) {
                    'manufacturer_admin' => 'Manufacturer Admin',
                    default => 'Manufacturer User',
                },
                'status' => $member->email_verified_at ? 'active' : 'pending',
                'last_updated' => $member->updated_at?->toDateString(),
            ])
            ->all();

        return Inertia::render('LineWatt/ManufacturerUsers', [
            'company' => [
                'name' => $company?->name ?? 'All Manufacturers',
                'plan_label' => match ($company?->plan_code ?? 'pro') {
                    'enterprise' => 'Enterprise',
                    default => 'Pro',
                },
                'max_users' => $company?->max_users,
                'user_count' => count($users),
                'can_manage_users' => $canManageUsers,
                'permission_message' => $canManageUsers
                    ? null
                    : 'Manufacturer Users cannot create users, delete users or manage subscription access.',
            ],
            'users' => $users,
        ]);
    }

    public function invite(Request $request): RedirectResponse
    {
        $user = $request->user();
        $isPlatformOperator = $user && in_array($user->role, [LineWattRole::SUPER_ADMIN, LineWattRole::ADMIN], true);

        if (! $isPlatformOperator && $user?->manufacturer_role !== 'manufacturer_admin') {
            return back()->with('error', 'Manufacturer Users cannot invite or manage users.');
        }

        return back()->with('success', 'Invitation workflow placeholder: email invitation and password reset will be connected in the next user-management milestone.');
    }
}
