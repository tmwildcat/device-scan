<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\EntitlementChecker;
use App\LineWatt\Access\LineWattRole;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryMemberController extends Controller
{
    private const PAID_MEMBER_PLANS = ['subscriber', 'demo_member'];
    private const ACTIVE_SUBSCRIPTION_STATUSES = ['active', 'trialing'];

    public function index(Request $request): Response
    {
        $view = str_replace('-', '_', (string) $request->query('view', $request->query('filter', 'all')));
        $view = in_array($view, ['all', 'subscribers', 'registered', 'suspended', 'usage_exports', 'support_access'], true) ? $view : 'all';

        $members = User::query()
            ->whereNotIn('role', [...LineWattRole::platformRoles(), ...LineWattRole::partnerRoles()])
            ->when($view === 'subscribers', fn (Builder $query) => $this->applyActiveSubscriberFilter($query))
            ->when($view === 'registered', fn (Builder $query) => $this->applyRegisteredFilter($query))
            ->when($view === 'suspended', fn (Builder $query) => $query->where('subscription_status', 'suspended'))
            ->when($view === 'support_access', fn (Builder $query) => $query->whereNotNull('entitlement_overrides'))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $member): array => $this->memberRow($member))
            ->toArray();

        return Inertia::render('LineWatt/LibraryAdminMembers', [
            'roleLabel' => LineWattRole::label($request->user()?->role),
            'view' => $view,
            'title' => $this->titleForView($view),
            'subtitle' => $this->subtitleForView($view),
            'members' => $members,
        ]);
    }

    public function show(Request $request, User $member, EntitlementChecker $entitlements): Response
    {
        $datasheets = DeviceDatasheet::query()
            ->where('source_type', 'tenant_private')
            ->where('tenant_id', $member->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (DeviceDatasheet $datasheet): array => [
                'id' => $datasheet->id,
                'uuid' => $datasheet->uuid,
                'manufacturer' => $datasheet->manufacturer,
                'product_name' => $datasheet->product_name,
                'device_type' => $datasheet->device_type,
                'status' => $datasheet->status,
                'filename' => $datasheet->datasheet_original_filename,
                'created_at' => $datasheet->created_at?->toDateString(),
            ])
            ->all();

        $records = CompiledDeviceRecord::query()
            ->where('source_type', 'tenant_private')
            ->where('tenant_id', $member->id)
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (CompiledDeviceRecord $record): array => [
                'id' => $record->id,
                'uuid' => $record->uuid,
                'manufacturer' => $record->manufacturer,
                'display_name' => $record->display_name,
                'device_type' => $record->device_type,
                'status' => $record->status,
                'validation_grade' => $record->validation_grade,
                'created_at' => $record->created_at?->toDateString(),
            ])
            ->all();

        return Inertia::render('LineWatt/LibraryAdminMemberDetail', [
            'roleLabel' => LineWattRole::label($request->user()?->role),
            'member' => [
                ...$this->memberRow($member),
                'entitlements' => $entitlements->entitlementsFor($member),
                'overrides' => $member->entitlement_overrides ?? [],
                'datasheets' => $datasheets,
                'records' => $records,
                'uploads_count' => DeviceDatasheet::query()->where('source_type', 'tenant_private')->where('tenant_id', $member->id)->count(),
                'private_records_count' => CompiledDeviceRecord::query()->where('source_type', 'tenant_private')->where('tenant_id', $member->id)->count(),
            ],
            'routes' => [
                'suspend' => route('admin.library.members.suspend', ['member' => $member]),
                'reactivate' => route('admin.library.members.reactivate', ['member' => $member]),
                'back' => route('admin.library.members'),
            ],
        ]);
    }

    public function suspend(Request $request, User $member): RedirectResponse
    {
        $member->forceFill([
            'subscription_status' => 'suspended',
        ])->save();

        return back()->with('success', 'Member suspended.');
    }

    public function reactivate(Request $request, User $member): RedirectResponse
    {
        $member->forceFill([
            'subscription_status' => $member->plan_code ? 'active' : 'registered',
        ])->save();

        return back()->with('success', 'Member reactivated.');
    }

    private function memberRow(User $member): array
    {
        return [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'role' => $member->role,
            'role_label' => LineWattRole::label($member->role),
            'plan_code' => $member->plan_code,
            'subscription_status' => $member->subscription_status,
            'created_at' => $member->created_at?->toDateString(),
            'last_activity' => $member->updated_at?->toDateString(),
            'href' => route('admin.library.members.show', ['member' => $member]),
        ];
    }

    private function applyActiveSubscriberFilter(Builder $query): void
    {
        $query
            ->where('role', LineWattRole::SUBSCRIBER)
            ->whereIn('plan_code', self::PAID_MEMBER_PLANS)
            ->whereIn('subscription_status', self::ACTIVE_SUBSCRIPTION_STATUSES);
    }

    private function applyRegisteredFilter(Builder $query): void
    {
        $query
            ->where('role', LineWattRole::GUEST)
            ->where(function (Builder $nested): void {
                $nested
                    ->whereNull('plan_code')
                    ->orWhere('plan_code', '')
                    ->orWhere('plan_code', 'none');
            })
            ->where(function (Builder $nested): void {
                $nested
                    ->whereNull('subscription_status')
                    ->orWhereNotIn('subscription_status', self::ACTIVE_SUBSCRIPTION_STATUSES);
            });
    }

    private function titleForView(string $view): string
    {
        return match ($view) {
            'subscribers' => 'Subscribers',
            'registered' => 'Registered Users',
            'suspended' => 'Suspended Members',
            'usage_exports' => 'Usage / Exports',
            'support_access' => 'Support',
            default => 'Members',
        };
    }

    private function subtitleForView(string $view): string
    {
        return match ($view) {
            'subscribers' => 'Users with active Subscriber library subscriptions.',
            'registered' => 'Users registered in the library without an active subscription plan.',
            'suspended' => 'Members whose library access is currently suspended.',
            'usage_exports' => 'Member usage, export and activity support view.',
            'support_access' => 'Members with support or entitlement override context.',
            default => 'Subscriber and registered member support, entitlement visibility, private upload metadata, and account operations.',
        };
    }
}
