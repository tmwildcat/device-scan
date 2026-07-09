<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\LineWattRole;
use Inertia\Inertia;
use Inertia\Response;

class ManufacturerUpgradeController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();
        $company = $user?->manufacturerCompany;

        return Inertia::render('LineWatt/ManufacturerUpgrade', [
            'company' => [
                'name' => $company?->name ?? 'Manufacturer Account',
                'plan_label' => match ($company?->plan_code) {
                    'enterprise' => 'Enterprise',
                    default => 'Pro',
                },
                'manufacturer_role' => $user?->manufacturer_role,
                'role_label' => LineWattRole::label($user?->role),
                'can_request_upgrade' => $user?->manufacturer_role === 'manufacturer_admin',
            ],
        ]);
    }
}
