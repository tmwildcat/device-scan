<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Activity\ActivityLogger;
use App\LineWatt\Access\LineWattRole;
use App\LineWatt\Notifications\NotificationManager;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\LibraryChampion;
use App\Models\ManufacturerCompany;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OemSubscriberController extends Controller
{
    public function index(): Response
    {
        $companies = [
            'data' => [],
            'links' => [],
            'current_page' => 1,
            'last_page' => 1,
            'from' => null,
            'to' => null,
            'total' => 0,
        ];

        if (Schema::hasTable('manufacturer_companies')) {
            $companies = ManufacturerCompany::query()
                ->withCount('users')
                ->latest()
                ->paginate(15)
                ->withQueryString()
                ->through(fn (ManufacturerCompany $company): array => $this->subscriberSummary($company))
                ->toArray();
        }

        return Inertia::render('LineWatt/OemSubscribers', [
            'roleLabel' => LineWattRole::label(auth()->user()?->role),
            'companies' => $companies,
            'statuses' => $this->statuses(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('LineWatt/OemSubscriberCreate', [
            'roleLabel' => LineWattRole::label(auth()->user()?->role),
            'plans' => ['pro' => 'Manufacturer Pro', 'enterprise' => 'Manufacturer Enterprise'],
            'statuses' => $this->statuses(),
            'initialManufacturer' => trim($request->string('manufacturer')->toString()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'manufacturer' => ['required', 'string', 'max:255'],
            'primary_contact_name' => ['required', 'string', 'max:255'],
            'primary_contact_email' => ['required', 'email', 'max:255'],
            'plan_code' => ['nullable', 'in:pro,enterprise'],
            'champion_id' => ['nullable', 'integer', 'exists:library_champions,id'],
            'referral_code' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $manufacturer = trim($data['manufacturer']);
        $slug = ManufacturerCompany::slugFor($manufacturer);

        if (ManufacturerCompany::query()->where('slug', $slug)->exists()) {
            return back()
                ->withErrors(['manufacturer' => 'This manufacturer already has an OEM Subscriber account.'])
                ->withInput();
        }

        $champion = $this->resolveChampion($request, $data['champion_id'] ?? null, $data['referral_code'] ?? null);
        if ($champion instanceof LibraryChampion && $champion->status === 'suspended' && ! in_array($request->user()?->role, [LineWattRole::ADMIN, LineWattRole::SUPER_ADMIN], true)) {
            return back()
                ->withErrors(['referral_code' => 'This Library Champion is suspended and cannot be assigned.'])
                ->withInput();
        }

        $token = Str::random(48);
        $company = ManufacturerCompany::create([
            'uuid' => (string) Str::uuid(),
            'name' => $manufacturer,
            'slug' => $slug,
            'plan_code' => $data['plan_code'] ?? 'pro',
            'subscription_status' => 'pending_invitation',
            'max_users' => 1,
            'champion_id' => $champion?->id,
            'referral_code' => $champion?->referral_code ?? ($data['referral_code'] ?? null),
            'referred_at' => $champion ? now() : null,
            'metadata' => [
                'primary_contact_name' => $data['primary_contact_name'],
                'primary_contact_email' => $data['primary_contact_email'],
                'invitation_token' => hash('sha256', $token),
                'invitation_sent_at' => now()->toIso8601String(),
                'invitation_status' => 'pending_invitation',
                'invitation_url' => route('manufacturer.register', ['token' => $token]),
                'notes' => $data['notes'] ?? null,
                'champion_assigned_by' => $champion ? $request->user()?->id : null,
                'onboarding_steps' => $this->onboardingSteps(),
            ],
        ]);

        $activity = app(ActivityLogger::class)->log('OemSubscriberInvitationCreated', $request->user(), $company, [
            'manufacturer_company_id' => $company->id,
            'library_champion_id' => $champion?->id,
        ]);

        if ($champion instanceof LibraryChampion) {
            app(NotificationManager::class)->notify(
                [$champion->user],
                'ChampionAssignedToOemSubscriber',
                'Champion referral assigned',
                $company->name.' has been linked to your Library Champion referral code.',
                route('champion.manufacturers.show', ['manufacturer' => $company]),
                $activity,
                ['library_champion_id' => $champion->id, 'manufacturer_company_id' => $company->id],
            );
        }

        return redirect()
            ->route('admin.library.oem-subscribers.show', ['subscriber' => $company])
            ->with('status', 'OEM Subscriber invitation generated.');
    }

    public function show(ManufacturerCompany $subscriber): Response
    {
        return Inertia::render('LineWatt/OemSubscriberDetail', [
            'roleLabel' => LineWattRole::label(auth()->user()?->role),
            'company' => [
                ...$this->subscriberSummary($subscriber),
                'metadata' => $subscriber->metadata ?? [],
                'champion' => $subscriber->champion ? [
                    'id' => $subscriber->champion->id,
                    'name' => $subscriber->champion->name,
                    'referral_code' => $subscriber->champion->referral_code,
                    'status' => $subscriber->champion->status,
                ] : null,
                'users' => $subscriber->users()
                    ->latest()
                    ->limit(10)
                    ->get()
                    ->map(fn (User $user): array => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => LineWattRole::label($user->role),
                        'manufacturer_role' => $user->manufacturer_role,
                        'status' => $user->subscription_status ?? 'active',
                    ])
                    ->all(),
                'invitation_url' => $subscriber->metadata['invitation_url'] ?? null,
                'onboarding_steps' => $subscriber->metadata['onboarding_steps'] ?? $this->onboardingSteps(),
            ],
            'statuses' => $this->statuses(),
        ]);
    }

    public function manufacturers(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $subscribedSlugs = ManufacturerCompany::query()->pluck('slug')->all();
        $names = collect();

        if (Schema::hasTable('compiled_device_records')) {
            $names = $names->merge(
                CompiledDeviceRecord::query()
                    ->whereNotNull('manufacturer')
                    ->where('manufacturer', '<>', '')
                    ->whereRaw('lower(manufacturer) like ?', ['%'.mb_strtolower($query).'%'])
                    ->distinct()
                    ->limit(40)
                    ->pluck('manufacturer')
            );
        }

        if (Schema::hasTable('device_datasheets')) {
            $names = $names->merge(
                DeviceDatasheet::query()
                    ->whereNotNull('manufacturer')
                    ->where('manufacturer', '<>', '')
                    ->whereRaw('lower(manufacturer) like ?', ['%'.mb_strtolower($query).'%'])
                    ->distinct()
                    ->limit(40)
                    ->pluck('manufacturer')
            );
        }

        return response()->json(
            $names
                ->map(fn (string $name): string => trim($name))
                ->filter()
                ->unique(fn (string $name): string => mb_strtolower($name))
                ->reject(fn (string $name): bool => in_array(ManufacturerCompany::slugFor($name), $subscribedSlugs, true))
                ->sortBy(fn (string $name): string => mb_strtolower($name))
                ->take(20)
                ->values()
                ->map(fn (string $name): array => [
                    'label' => $name,
                    'value' => $name,
                ])
                ->all()
        );
    }

    public function register(string $token): Response
    {
        $company = $this->companyForToken($token);
        abort_unless($company, 404);

        return Inertia::render('LineWatt/ManufacturerInvitation', [
            'company' => [
                'name' => $company->name,
                'plan' => $company->plan_label,
                'status' => $this->statusLabel($company->subscription_status),
                'primary_contact_name' => $company->metadata['primary_contact_name'] ?? null,
                'primary_contact_email' => $company->metadata['primary_contact_email'] ?? null,
            ],
            'token' => $token,
            'steps' => $company->metadata['onboarding_steps'] ?? $this->onboardingSteps(),
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $company = $this->companyForToken($token);
        abort_unless($company, 404);

        $metadata = $company->metadata ?? [];
        $metadata['invitation_status'] = 'accepted';
        $metadata['accepted_at'] = now()->toIso8601String();
        $metadata['paddle_checkout_status'] = 'placeholder';

        $company->forceFill([
            'subscription_status' => 'accepted',
            'metadata' => $metadata,
        ])->save();

        return back()->with('status', 'Invitation accepted. Account creation, verification and Paddle checkout are placeholders for the next milestone.');
    }

    /**
     * @return array<string,string>
     */
    private function statuses(): array
    {
        return [
            'pending_invitation' => 'Pending Invitation',
            'accepted' => 'Accepted',
            'trial' => 'Trial',
            'active' => 'Active',
            'expired' => 'Expired',
            'suspended' => 'Suspended',
            'cancelled' => 'Cancelled',
            'contract_active' => 'Active',
        ];
    }

    /**
     * @return list<array{label:string,status:string,description:string}>
     */
    private function onboardingSteps(): array
    {
        return [
            ['label' => 'Accept invitation', 'status' => 'available', 'description' => 'Primary contact confirms the OEM invitation.'],
            ['label' => 'Create account', 'status' => 'placeholder', 'description' => 'Manufacturer Admin account creation will be completed in the next onboarding milestone.'],
            ['label' => 'Email verification', 'status' => 'placeholder', 'description' => 'The admin verifies their official email domain.'],
            ['label' => 'Paddle Checkout', 'status' => 'placeholder', 'description' => 'Checkout is intentionally not implemented yet.'],
            ['label' => 'Choose plan', 'status' => 'placeholder', 'description' => 'Manufacturer Pro is self-serve; Manufacturer Enterprise is contact-sales unless a Paddle price is configured.'],
            ['label' => 'Payment', 'status' => 'placeholder', 'description' => 'Payment activates subscription state later.'],
            ['label' => 'Manufacturer Admin activated', 'status' => 'placeholder', 'description' => 'Manufacturer Admin access turns on after verification/payment.'],
            ['label' => 'Invite team', 'status' => 'placeholder', 'description' => 'Admin can invite manufacturer users within plan limits.'],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function subscriberSummary(ManufacturerCompany $company): array
    {
        $recordBase = CompiledDeviceRecord::query()->where('manufacturer', $company->name);
        $datasheetBase = DeviceDatasheet::query()->where('manufacturer', $company->name);

        return [
            'id' => $company->id,
            'uuid' => $company->uuid,
            'name' => $company->name,
            'slug' => $company->slug,
            'plan' => $company->plan_label,
            'plan_code' => $company->plan_code,
            'status' => $company->subscription_status,
            'status_label' => $this->statusLabel($company->subscription_status),
            'referral_code' => $company->referral_code,
            'primary_contact_name' => $company->metadata['primary_contact_name'] ?? null,
            'primary_contact_email' => $company->metadata['primary_contact_email'] ?? null,
            'datasheets' => (clone $datasheetBase)->count(),
            'records' => (clone $recordBase)->count(),
            'users_count' => $company->users_count ?? $company->users()->count(),
            'pending_submissions' => (clone $recordBase)
                ->where(function ($query): void {
                    $query->where('status', 'submitted_for_approval')->orWhere('review_status', 'submitted');
                })
                ->count(),
            'champion' => $company->champion ? [
                'id' => $company->champion->id,
                'name' => $company->champion->name,
                'referral_code' => $company->champion->referral_code,
                'status' => $company->champion->status,
            ] : null,
            'href' => route('admin.library.oem-subscribers.show', ['subscriber' => $company]),
        ];
    }

    private function statusLabel(?string $status): string
    {
        return $this->statuses()[$status ?: 'pending_invitation'] ?? str($status ?: 'pending_invitation')->replace('_', ' ')->title()->toString();
    }

    private function companyForToken(string $token): ?ManufacturerCompany
    {
        return ManufacturerCompany::query()
            ->where('metadata->invitation_token', hash('sha256', $token))
            ->first();
    }

    private function resolveChampion(Request $request, mixed $championId, ?string $referralCode): ?LibraryChampion
    {
        if ($championId) {
            return LibraryChampion::query()->with('user')->find((int) $championId);
        }

        $code = Str::upper(trim((string) $referralCode));

        if ($code === '') {
            return null;
        }

        $champion = LibraryChampion::query()->with('user')->where('referral_code', $code)->first();

        if (! $champion) {
            throw ValidationException::withMessages([
                'referral_code' => 'No Library Champion was found for that referral code.',
            ]);
        }

        return $champion;
    }
}
