<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\LineWattRole;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\LibraryChampion;
use App\Models\ManufacturerCompany;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChampionDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $champion = $this->championFor($request);
        abort_unless($champion, 403);

        $manufacturers = $champion->manufacturerCompanies()
            ->latest()
            ->get()
            ->map(fn (ManufacturerCompany $company): array => $this->manufacturerRow($company))
            ->all();

        return Inertia::render('LineWatt/ChampionDashboard', [
            'roleLabel' => LineWattRole::label($request->user()?->role),
            'champion' => [
                'name' => $champion->name,
                'email' => $champion->email,
                'referral_code' => $champion->referral_code,
                'status' => $champion->status,
                'organisation' => $champion->organisation,
            ],
            'summary' => [
                'recruited_manufacturers' => count($manufacturers),
                'active_subscriptions' => collect($manufacturers)->whereIn('subscription_status', ['active', 'contract_active', 'trial'])->count(),
                'pending_invitations' => collect($manufacturers)->where('subscription_status', 'pending_invitation')->count(),
                'estimated_commission' => 'Placeholder',
            ],
            'manufacturers' => $manufacturers,
        ]);
    }

    public function manufacturer(Request $request, ManufacturerCompany $manufacturer): Response
    {
        $champion = $this->championFor($request);
        abort_unless($champion && (int) $manufacturer->champion_id === (int) $champion->id, 403);

        return Inertia::render('LineWatt/ChampionManufacturerDetail', [
            'roleLabel' => LineWattRole::label($request->user()?->role),
            'champion' => [
                'name' => $champion->name,
                'referral_code' => $champion->referral_code,
            ],
            'manufacturer' => $this->manufacturerRow($manufacturer),
        ]);
    }

    private function championFor(Request $request): ?LibraryChampion
    {
        return LibraryChampion::query()->where('user_id', $request->user()?->id)->first();
    }

    /**
     * @return array<string,mixed>
     */
    private function manufacturerRow(ManufacturerCompany $company): array
    {
        $datasheets = DeviceDatasheet::query()->where('manufacturer', $company->name)->count();
        $records = CompiledDeviceRecord::query()->where('manufacturer', $company->name)->count();

        return [
            'id' => $company->id,
            'name' => $company->name,
            'slug' => $company->slug,
            'plan' => $company->plan_label,
            'plan_code' => $company->plan_code,
            'subscription_status' => $company->subscription_status,
            'subscribed_from' => $company->referred_at?->toDateString() ?? $company->created_at?->toDateString(),
            'invitation_status' => $company->metadata['invitation_status'] ?? $company->subscription_status,
            'users_count' => $company->users()->count(),
            'datasheets' => $datasheets,
            'records' => $records,
            'last_activity' => $company->updated_at?->toDateString(),
            'href' => route('champion.manufacturers.show', ['manufacturer' => $company]),
        ];
    }
}
