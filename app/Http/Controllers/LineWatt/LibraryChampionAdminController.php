<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Activity\ActivityLogger;
use App\LineWatt\Access\LineWattRole;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\LibraryChampion;
use App\Models\ManufacturerCompany;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LibraryChampionAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $champions = LibraryChampion::query()
            ->with('user')
            ->withCount([
                'manufacturerCompanies as recruited_manufacturers_count',
                'manufacturerCompanies as active_subscriptions_count' => fn ($query) => $query->whereIn('subscription_status', ['active', 'contract_active', 'trial']),
            ])
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (LibraryChampion $champion): array => $this->row($champion))
            ->toArray();

        return Inertia::render('LineWatt/LibraryChampions', [
            'roleLabel' => LineWattRole::label($request->user()?->role),
            'champions' => $champions,
            'statuses' => ['active' => 'Active', 'paused' => 'Paused', 'suspended' => 'Suspended', 'archived' => 'Archived'],
            'commissionTypes' => ['percent' => 'Percent', 'fixed' => 'Fixed', 'custom' => 'Custom'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $user = User::query()->where('email', $data['email'])->first();

        if ($user instanceof User && $user->role !== LineWattRole::LIBRARY_CHAMPION) {
            throw ValidationException::withMessages([
                'email' => 'This email belongs to an existing non-champion user.',
            ]);
        }

        $user ??= User::query()->create([
                'name' => $data['name'],
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                'role' => LineWattRole::LIBRARY_CHAMPION,
                'plan_code' => null,
                'subscription_status' => null,
        ]);

        $user->forceFill([
            'name' => $data['name'],
            'role' => LineWattRole::LIBRARY_CHAMPION,
        ])->save();

        $champion = LibraryChampion::create([
            ...$data,
            'user_id' => $user->id,
        ]);

        app(ActivityLogger::class)->log('LibraryChampionCreated', $request->user(), $champion, [
            'library_champion_id' => $champion->id,
            'referral_code' => $champion->referral_code,
        ]);

        return back()->with('success', 'Library Champion created. Invitation/password flow is a placeholder.');
    }

    public function show(Request $request, LibraryChampion $champion): Response
    {
        $champion->load('user');

        $manufacturers = $champion->manufacturerCompanies()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (ManufacturerCompany $company): array => $this->manufacturerRow($company, $champion))
            ->all();

        return Inertia::render('LineWatt/LibraryChampionDetail', [
            'roleLabel' => LineWattRole::label($request->user()?->role),
            'champion' => [
                ...$this->row($champion),
                'user' => $champion->user ? [
                    'name' => $champion->user->name,
                    'email' => $champion->user->email,
                ] : null,
            ],
            'manufacturers' => $manufacturers,
            'statuses' => ['active' => 'Active', 'paused' => 'Paused', 'suspended' => 'Suspended', 'archived' => 'Archived'],
            'commissionTypes' => ['percent' => 'Percent', 'fixed' => 'Fixed', 'custom' => 'Custom'],
        ]);
    }

    public function update(Request $request, LibraryChampion $champion): RedirectResponse
    {
        $champion->fill($this->validated($request, $champion))->save();

        app(ActivityLogger::class)->log('LibraryChampionUpdated', $request->user(), $champion, [
            'library_champion_id' => $champion->id,
        ]);

        return back()->with('success', 'Library Champion updated.');
    }

    public function pause(Request $request, LibraryChampion $champion): RedirectResponse
    {
        $nextStatus = $champion->status === 'paused' ? 'active' : 'paused';

        $champion->forceFill(['status' => $nextStatus])->save();
        app(ActivityLogger::class)->log($nextStatus === 'paused' ? 'LibraryChampionPaused' : 'LibraryChampionUnpaused', $request->user(), $champion, ['library_champion_id' => $champion->id]);

        return back()->with('success', $nextStatus === 'paused' ? 'Library Champion paused.' : 'Library Champion unpaused.');
    }

    public function suspend(Request $request, LibraryChampion $champion): RedirectResponse
    {
        $nextStatus = $champion->status === 'suspended' ? 'active' : 'suspended';

        $champion->forceFill(['status' => $nextStatus])->save();
        app(ActivityLogger::class)->log($nextStatus === 'suspended' ? 'LibraryChampionSuspended' : 'LibraryChampionReinstated', $request->user(), $champion, ['library_champion_id' => $champion->id]);

        return back()->with('success', $nextStatus === 'suspended' ? 'Library Champion suspended.' : 'Library Champion reinstated.');
    }

    public function manufacturerSearch(Request $request, LibraryChampion $champion): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $needle = '%'.mb_strtolower($query).'%';

        return response()->json(
            ManufacturerCompany::query()
                ->whereRaw('lower(name) like ?', [$needle])
                ->where(function ($builder) use ($champion): void {
                    $builder
                        ->whereNull('champion_id')
                        ->orWhere('champion_id', '<>', $champion->id);
                })
                ->orderBy('name')
                ->limit(20)
                ->get()
                ->map(fn (ManufacturerCompany $company): array => [
                    'label' => $company->name,
                    'value' => $company->id,
                    'plan' => $company->plan_code,
                    'subscription_status' => $company->subscription_status,
                    'current_champion' => $company->champion_id,
                ])
                ->all()
        );
    }

    public function assignManufacturer(Request $request, LibraryChampion $champion): RedirectResponse
    {
        $data = $request->validate([
            'manufacturer_company_id' => ['required', 'integer', 'exists:manufacturer_companies,id'],
        ]);

        $company = ManufacturerCompany::query()->findOrFail($data['manufacturer_company_id']);

        $company->forceFill([
            'champion_id' => $champion->id,
            'referral_code' => $champion->referral_code,
            'referred_at' => $company->referred_at ?? now(),
        ])->save();

        app(ActivityLogger::class)->log('LibraryChampionManufacturerAssigned', $request->user(), $champion, [
            'library_champion_id' => $champion->id,
            'manufacturer_company_id' => $company->id,
        ]);

        return back()->with('success', $company->name.' tagged to '.$champion->name.'.');
    }

    public function removeManufacturer(Request $request, LibraryChampion $champion, ManufacturerCompany $manufacturer): RedirectResponse
    {
        if ((int) $manufacturer->champion_id !== (int) $champion->id) {
            return back()->with('error', $manufacturer->name.' is not tagged to this champion.');
        }

        $manufacturer->forceFill([
            'champion_id' => null,
            'referral_code' => null,
            'referred_at' => null,
        ])->save();

        app(ActivityLogger::class)->log('LibraryChampionManufacturerRemoved', $request->user(), $champion, [
            'library_champion_id' => $champion->id,
            'manufacturer_company_id' => $manufacturer->id,
        ]);

        return back()->with('success', $manufacturer->name.' removed from '.$champion->name.'.');
    }

    public function search(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        return response()->json(
            LibraryChampion::query()
                ->where(function ($builder) use ($query): void {
                    $needle = '%'.mb_strtolower($query).'%';
                    $builder
                        ->whereRaw('lower(name) like ?', [$needle])
                        ->orWhereRaw('lower(email) like ?', [$needle])
                        ->orWhereRaw('lower(referral_code) like ?', [$needle]);
                })
                ->orderBy('name')
                ->limit(20)
                ->get()
                ->map(fn (LibraryChampion $champion): array => [
                    'label' => $champion->name.' · '.$champion->referral_code,
                    'value' => $champion->id,
                    'referral_code' => $champion->referral_code,
                    'status' => $champion->status,
                ])
                ->all()
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function validated(Request $request, ?LibraryChampion $champion = null): array
    {
        $request->merge([
            'referral_code' => Str::upper(trim((string) $request->input('referral_code'))),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:80'],
            'organisation' => ['nullable', 'string', 'max:160'],
            'status' => ['required', 'in:active,paused,suspended,archived'],
            'referral_code' => ['required', 'string', 'max:80', 'unique:library_champions,referral_code,'.($champion?->id ?? 'NULL').',id'],
            'commission_type' => ['required', 'in:percent,fixed,custom'],
            'commission_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function row(LibraryChampion $champion): array
    {
        return [
            'id' => $champion->id,
            'uuid' => $champion->uuid,
            'name' => $champion->name,
            'email' => $champion->email,
            'phone' => $champion->phone,
            'organisation' => $champion->organisation,
            'status' => $champion->status,
            'referral_code' => $champion->referral_code,
            'commission_type' => $champion->commission_type,
            'commission_value' => $champion->commission_value,
            'notes' => $champion->notes,
            'recruited_manufacturers_count' => $champion->recruited_manufacturers_count ?? 0,
            'active_subscriptions_count' => $champion->active_subscriptions_count ?? 0,
            'routes' => [
                'show' => route('admin.library.champions.show', ['champion' => $champion]),
                'update' => route('admin.library.champions.update', ['champion' => $champion]),
                'pause' => route('admin.library.champions.pause', ['champion' => $champion]),
                'suspend' => route('admin.library.champions.suspend', ['champion' => $champion]),
                'manufacturer_search' => route('admin.library.champions.manufacturers.search', ['champion' => $champion]),
                'assign_manufacturer' => route('admin.library.champions.manufacturers.assign', ['champion' => $champion]),
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function manufacturerRow(ManufacturerCompany $company, LibraryChampion $champion): array
    {
        $manufacturer = mb_strtolower($company->name);
        $datasheets = DeviceDatasheet::query()
            ->whereRaw('lower(manufacturer) = ?', [$manufacturer])
            ->count();
        $records = CompiledDeviceRecord::query()
            ->whereRaw('lower(manufacturer) = ?', [$manufacturer])
            ->count();

        return [
            'id' => $company->id,
            'uuid' => $company->uuid,
            'name' => $company->name,
            'slug' => $company->slug,
            'plan_code' => $company->plan_code,
            'subscription_status' => $company->subscription_status,
            'subscribed_from' => $company->referred_at?->toDateString() ?? $company->created_at?->toDateString(),
            'referral_code' => $company->referral_code ?: $champion->referral_code,
            'commission_type' => $champion->commission_type,
            'commission_value' => $champion->commission_value,
            'users_count' => $company->users_count ?? 0,
            'datasheets_count' => $datasheets,
            'engineering_data_count' => $records,
            'last_activity' => collect([$company->updated_at, $company->referred_at, $company->created_at])->filter()->sortDesc()->first()?->toDateString(),
            'href' => route('admin.library.oem-subscribers.show', ['subscriber' => $company]),
            'remove_href' => route('admin.library.champions.manufacturers.remove', ['champion' => $champion, 'manufacturer' => $company]),
        ];
    }
}
