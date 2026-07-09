<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Activity\ActivityLogger;
use App\LineWatt\Access\LineWattRole;
use App\LineWatt\Notifications\NotificationManager;
use App\Models\LibraryChampion;
use App\Models\ManufacturerCompany;
use App\Models\PartnerRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PartnerRequestController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('LineWatt/PartnerApply');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:160'],
            'website' => ['nullable', 'url', 'max:200'],
            'country' => ['required', 'string', 'max:120'],
            'contact_person' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email', 'max:160'],
            'official_email_domain' => ['nullable', 'string', 'max:120'],
            'requested_manufacturer_brand' => ['required', 'string', 'max:160'],
            'referral_code' => ['nullable', 'string', 'max:80'],
            'proof_notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $champion = $this->resolveChampion(null, $validated['referral_code'] ?? null, false);

        $partnerRequest = PartnerRequest::create([
            ...$validated,
            'official_email_domain' => $validated['official_email_domain']
                ? Str::lower(trim($validated['official_email_domain']))
                : Str::after(Str::lower($validated['contact_email']), '@'),
            'status' => 'pending',
            'champion_id' => $champion?->id,
            'referral_code' => $champion?->referral_code ?? ($validated['referral_code'] ?? null),
            'referred_at' => $champion ? now() : null,
        ]);

        $activity = app(ActivityLogger::class)->log('PartnerRequestCreated', $request->user(), $partnerRequest, [
            'partner_request_id' => $partnerRequest->id,
            'company_name' => $partnerRequest->company_name,
        ]);

        app(NotificationManager::class)->notifyLibrarians(
            'PartnerRequestCreated',
            'New OEM partner request',
            $partnerRequest->company_name.' requested Manufacturer Admin access.',
            route('admin.library.partner-requests.show', ['partnerRequest' => $partnerRequest]),
            $activity,
            true,
        );

        return back()->with('success', 'Your partner request has been submitted for LineWatt Library review.');
    }

    public function index(Request $request): Response
    {
        $status = $request->query('status');

        $requests = PartnerRequest::query()
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (PartnerRequest $partnerRequest): array => $this->requestRow($partnerRequest))
            ->toArray();

        return Inertia::render('LineWatt/LibraryAdminPartnerRequests', [
            'roleLabel' => LineWattRole::label($request->user()?->role),
            'status' => $status,
            'requests' => $requests,
        ]);
    }

    public function show(Request $request, PartnerRequest $partnerRequest): Response
    {
        return Inertia::render('LineWatt/LibraryAdminPartnerRequestDetail', [
            'roleLabel' => LineWattRole::label($request->user()?->role),
            'partnerRequest' => [
                ...$this->requestRow($partnerRequest),
                'proof_notes' => $partnerRequest->proof_notes,
                'review_comment' => $partnerRequest->review_comment,
                'reviewed_by' => $partnerRequest->reviewer?->name,
                'reviewed_at' => $partnerRequest->reviewed_at?->toDateTimeString(),
                'linked_company' => $partnerRequest->manufacturerCompany ? [
                    'id' => $partnerRequest->manufacturerCompany->id,
                    'name' => $partnerRequest->manufacturerCompany->name,
                    'href' => route('admin.library.oems.show', ['oem' => $partnerRequest->manufacturerCompany]),
                ] : null,
                'champion' => $partnerRequest->champion ? [
                    'id' => $partnerRequest->champion->id,
                    'name' => $partnerRequest->champion->name,
                    'referral_code' => $partnerRequest->champion->referral_code,
                    'status' => $partnerRequest->champion->status,
                ] : null,
            ],
            'routes' => [
                'approve' => route('admin.library.partner-requests.approve', ['partnerRequest' => $partnerRequest]),
                'reject' => route('admin.library.partner-requests.reject', ['partnerRequest' => $partnerRequest]),
                'requestInfo' => route('admin.library.partner-requests.request-info', ['partnerRequest' => $partnerRequest]),
                'back' => route('admin.library.partner-requests'),
            ],
        ]);
    }

    public function approve(Request $request, PartnerRequest $partnerRequest): RedirectResponse
    {
        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:2000'],
            'plan_code' => ['nullable', 'in:pro,enterprise'],
            'champion_id' => ['nullable', 'integer', 'exists:library_champions,id'],
            'referral_code' => ['nullable', 'string', 'max:80'],
        ]);

        $champion = $this->resolveChampion($validated['champion_id'] ?? $partnerRequest->champion_id, $validated['referral_code'] ?? $partnerRequest->referral_code, true);

        $company = ManufacturerCompany::query()
            ->where('slug', ManufacturerCompany::slugFor($partnerRequest->requested_manufacturer_brand ?: $partnerRequest->company_name))
            ->orWhere('name', $partnerRequest->requested_manufacturer_brand ?: $partnerRequest->company_name)
            ->first();

        $company ??= ManufacturerCompany::create([
            'uuid' => (string) Str::uuid(),
            'name' => $partnerRequest->requested_manufacturer_brand ?: $partnerRequest->company_name,
            'slug' => ManufacturerCompany::slugFor($partnerRequest->requested_manufacturer_brand ?: $partnerRequest->company_name),
            'plan_code' => $validated['plan_code'] ?? 'pro',
            'subscription_status' => 'contract_pending',
            'max_users' => 1,
            'champion_id' => $champion?->id,
            'referral_code' => $champion?->referral_code ?? ($validated['referral_code'] ?? $partnerRequest->referral_code),
            'referred_at' => $champion ? now() : null,
            'metadata' => [
                'created_from_partner_request_id' => $partnerRequest->id,
                'website' => $partnerRequest->website,
                'country' => $partnerRequest->country,
                'official_email_domain' => $partnerRequest->official_email_domain,
                'first_admin_invitation_placeholder' => $partnerRequest->contact_email,
            ],
        ]);

        if ($champion instanceof LibraryChampion && ! $company->champion_id) {
            $company->forceFill([
                'champion_id' => $champion->id,
                'referral_code' => $champion->referral_code,
                'referred_at' => now(),
            ])->save();
        }

        $partnerRequest->forceFill([
            'status' => 'approved',
            'manufacturer_company_id' => $company->id,
            'champion_id' => $champion?->id,
            'referral_code' => $champion?->referral_code ?? ($validated['referral_code'] ?? $partnerRequest->referral_code),
            'referred_at' => $champion ? ($partnerRequest->referred_at ?? now()) : null,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'review_comment' => $validated['comment'] ?? null,
            'metadata' => [
                ...($partnerRequest->metadata ?? []),
                'first_manufacturer_admin_invite_placeholder' => $partnerRequest->contact_email,
                'approved_plan_code' => $company->plan_code,
            ],
        ])->save();

        $activity = app(ActivityLogger::class)->log('PartnerRequestApproved', $request->user(), $partnerRequest, [
            'partner_request_id' => $partnerRequest->id,
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

        return redirect()->route('admin.library.partner-requests.show', ['partnerRequest' => $partnerRequest])
            ->with('success', 'Partner request approved. Manufacturer company is ready; first Manufacturer Admin invitation remains a placeholder.');
    }

    public function reject(Request $request, PartnerRequest $partnerRequest): RedirectResponse
    {
        return $this->transition($request, $partnerRequest, 'rejected', 'Partner request rejected.');
    }

    public function requestInfo(Request $request, PartnerRequest $partnerRequest): RedirectResponse
    {
        return $this->transition($request, $partnerRequest, 'more_information_requested', 'More information requested from applicant.');
    }

    private function transition(Request $request, PartnerRequest $partnerRequest, string $status, string $message): RedirectResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:2000'],
        ]);

        $partnerRequest->forceFill([
            'status' => $status,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'review_comment' => $validated['comment'],
        ])->save();

        app(ActivityLogger::class)->log('PartnerRequest'.Str::studly($status), $request->user(), $partnerRequest, [
            'partner_request_id' => $partnerRequest->id,
            'comment' => $validated['comment'],
        ]);

        return back()->with('success', $message);
    }

    private function requestRow(PartnerRequest $partnerRequest): array
    {
        return [
            'id' => $partnerRequest->id,
            'uuid' => $partnerRequest->uuid,
            'status' => $partnerRequest->status,
            'company_name' => $partnerRequest->company_name,
            'website' => $partnerRequest->website,
            'country' => $partnerRequest->country,
            'contact_person' => $partnerRequest->contact_person,
            'contact_email' => $partnerRequest->contact_email,
            'official_email_domain' => $partnerRequest->official_email_domain,
            'requested_manufacturer_brand' => $partnerRequest->requested_manufacturer_brand,
            'created_at' => $partnerRequest->created_at?->toDateString(),
            'champion' => $partnerRequest->champion ? [
                'id' => $partnerRequest->champion->id,
                'name' => $partnerRequest->champion->name,
                'referral_code' => $partnerRequest->champion->referral_code,
                'status' => $partnerRequest->champion->status,
            ] : null,
            'href' => route('admin.library.partner-requests.show', ['partnerRequest' => $partnerRequest]),
        ];
    }

    private function resolveChampion(mixed $championId, ?string $referralCode, bool $rejectSuspended): ?LibraryChampion
    {
        if ($championId) {
            $champion = LibraryChampion::query()->with('user')->find((int) $championId);
        } else {
            $code = Str::upper(trim((string) $referralCode));
            $champion = $code !== ''
                ? LibraryChampion::query()->with('user')->where('referral_code', $code)->first()
                : null;
        }

        if (! $champion) {
            return null;
        }

        if ($rejectSuspended && $champion->status === 'suspended') {
            throw ValidationException::withMessages([
                'referral_code' => 'This Library Champion is suspended and cannot be assigned.',
            ]);
        }

        return $champion;
    }
}
