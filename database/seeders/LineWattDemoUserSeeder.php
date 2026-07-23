<?php

namespace Database\Seeders;

use App\LineWatt\Access\LineWattRole;
use App\Models\LibraryChampion;
use App\Models\ManufacturerCompany;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LineWattDemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'LineWatt Super Admin', 'email' => 'super@linewatt.test', 'role' => LineWattRole::SUPER_ADMIN],
            ['name' => 'LineWatt Admin', 'email' => 'admin@linewatt.test', 'role' => LineWattRole::ADMIN],
            ['name' => 'LineWatt Librarian', 'email' => 'librarian@linewatt.test', 'role' => LineWattRole::LIBRARIAN],
            ['name' => 'LineWatt Library Publisher', 'email' => 'library-publisher@linewatt.test', 'role' => LineWattRole::LIBRARY_PUBLISHER],
            ['name' => 'Demo Library Champion One', 'email' => 'library-champion@linewatt.test', 'role' => LineWattRole::LIBRARY_CHAMPION],
            ['name' => 'Demo Library Champion Two', 'email' => 'library-champion-2@linewatt.test', 'role' => LineWattRole::LIBRARY_CHAMPION],
            ['name' => 'LineWatt Registered User', 'email' => 'registered@linewatt.test', 'role' => LineWattRole::GUEST],
            ['name' => 'LineWatt Subscriber', 'email' => 'subscriber@linewatt.test', 'role' => LineWattRole::SUBSCRIBER],
            ['name' => 'Partner Admin', 'email' => 'partner-admin@linewatt.test', 'role' => LineWattRole::PARTNER_ADMIN],
            ['name' => 'Partner User', 'email' => 'partner-user@linewatt.test', 'role' => LineWattRole::PARTNER_USER],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make(env('DEMO_USER_PASSWORD', 'password')),
                    'email_verified_at' => now(),
                    'role' => $user['role'],
                    'plan_code' => $this->planCodeFor($user['role']),
                    'subscription_status' => $this->subscriptionStatusFor($user['role']),
                    'manufacturer_company_id' => null,
                    'manufacturer_role' => null,
                    'entitlement_overrides' => null,
                ]
            );
        }

        $champions = $this->demoChampions();

        foreach ($this->manufacturerCompanies() as $companyData) {
            $champion = $champions[$companyData['champion_referral_code'] ?? ''] ?? null;

            $company = ManufacturerCompany::query()->updateOrCreate(
                ['slug' => ManufacturerCompany::slugFor($companyData['name'])],
                [
                    'uuid' => $companyData['uuid'],
                    'name' => $companyData['name'],
                    'plan_code' => $companyData['plan_code'],
                    'subscription_status' => 'contract_active',
                    'max_users' => $companyData['max_users'],
                    'champion_id' => $champion?->id,
                    'referral_code' => $champion?->referral_code,
                    'referred_at' => $champion ? now() : null,
                    'metadata' => [
                        'logo_placeholder' => true,
                        'manufacturer_aliases' => $companyData['aliases'],
                    ],
                ]
            );

            foreach ($companyData['users'] as $user) {
                User::query()->updateOrCreate(
                    ['email' => $user['email']],
                    [
                        'name' => $user['name'],
                        'password' => Hash::make(env('DEMO_USER_PASSWORD', 'password')),
                        'email_verified_at' => now(),
                        'role' => $user['manufacturer_role'] === 'manufacturer_admin'
                            ? LineWattRole::PARTNER_ADMIN
                            : LineWattRole::PARTNER_USER,
                        'plan_code' => 'manufacturer_'.$companyData['plan_code'],
                        'subscription_status' => 'contract_active',
                        'manufacturer_company_id' => $company->id,
                        'manufacturer_role' => $user['manufacturer_role'],
                        'entitlement_overrides' => null,
                    ]
                );
            }
        }
    }

    /**
     * @return array<string,LibraryChampion>
     */
    private function demoChampions(): array
    {
        $champions = [];

        foreach ([
            [
                'uuid' => '44444444-4444-4444-8444-444444444444',
                'name' => 'Demo Library Champion One',
                'email' => 'library-champion@linewatt.test',
                'organisation' => 'LineWatt Friends',
                'referral_code' => 'CHAMPION-DEMO-1',
                'notes' => 'Demo champion with two recruited manufacturer subscribers.',
            ],
            [
                'uuid' => '55555555-5555-4555-8555-555555555555',
                'name' => 'Demo Library Champion Two',
                'email' => 'library-champion-2@linewatt.test',
                'organisation' => 'LineWatt Partners',
                'referral_code' => 'CHAMPION-DEMO-2',
                'notes' => 'Demo champion with one recruited manufacturer subscriber.',
            ],
        ] as $data) {
            $user = User::query()->where('email', $data['email'])->first();

            if (! $user) {
                continue;
            }

            $champions[$data['referral_code']] = LibraryChampion::query()->updateOrCreate(
                ['referral_code' => $data['referral_code']],
                [
                    'uuid' => $data['uuid'],
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => null,
                    'organisation' => $data['organisation'],
                    'status' => 'active',
                    'commission_type' => 'custom',
                    'commission_value' => null,
                    'notes' => $data['notes'],
                ]
            );
        }

        return $champions;
    }

    private function planCodeFor(string $role): ?string
    {
        return match ($role) {
            LineWattRole::SUBSCRIBER => 'subscriber',
            LineWattRole::PARTNER_ADMIN,
            LineWattRole::PARTNER_USER => 'demo_partner_contract',
            default => null,
        };
    }

    private function subscriptionStatusFor(string $role): ?string
    {
        return match ($role) {
            LineWattRole::SUBSCRIBER => 'active',
            LineWattRole::PARTNER_ADMIN,
            LineWattRole::PARTNER_USER => 'contract_active',
            default => null,
        };
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function manufacturerCompanies(): array
    {
        return [
            [
                'uuid' => '11111111-1111-4111-8111-111111111111',
                'name' => 'Trina Solar',
                'plan_code' => 'pro',
                'max_users' => 3,
                'champion_referral_code' => 'CHAMPION-DEMO-1',
                'aliases' => ['Trina Solar', 'Trina'],
                'users' => [
                    ['name' => 'Trina Solar Admin', 'email' => 'trina-admin@linewatt.test', 'manufacturer_role' => 'manufacturer_admin'],
                    ['name' => 'Trina Solar User', 'email' => 'trina-user@linewatt.test', 'manufacturer_role' => 'manufacturer_user'],
                ],
            ],
            [
                'uuid' => '22222222-2222-4222-8222-222222222222',
                'name' => 'Vikram Solar',
                'plan_code' => 'pro',
                'max_users' => 3,
                'champion_referral_code' => 'CHAMPION-DEMO-1',
                'aliases' => ['Vikram Solar', 'Vikram'],
                'users' => [
                    ['name' => 'Vikram Solar Admin', 'email' => 'vikram-admin@linewatt.test', 'manufacturer_role' => 'manufacturer_admin'],
                    ['name' => 'Vikram Solar User 1', 'email' => 'vikram-user1@linewatt.test', 'manufacturer_role' => 'manufacturer_user'],
                    ['name' => 'Vikram Solar User 2', 'email' => 'vikram-user2@linewatt.test', 'manufacturer_role' => 'manufacturer_user'],
                ],
            ],
            [
                'uuid' => '33333333-3333-4333-8333-333333333333',
                'name' => 'Canadian Solar',
                'plan_code' => 'enterprise',
                'max_users' => 10,
                'champion_referral_code' => 'CHAMPION-DEMO-2',
                'aliases' => ['Canadian Solar', 'Canadian'],
                'users' => [
                    ['name' => 'Canadian Solar Admin', 'email' => 'canadian-admin@linewatt.test', 'manufacturer_role' => 'manufacturer_admin'],
                    ['name' => 'Canadian Solar User 1', 'email' => 'canadian-user1@linewatt.test', 'manufacturer_role' => 'manufacturer_user'],
                    ['name' => 'Canadian Solar User 2', 'email' => 'canadian-user2@linewatt.test', 'manufacturer_role' => 'manufacturer_user'],
                    ['name' => 'Canadian Solar User 3', 'email' => 'canadian-user3@linewatt.test', 'manufacturer_role' => 'manufacturer_user'],
                ],
            ],
        ];
    }
}
