<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PowerSearchTaxonomySeeder::class);
        $this->call(PlatformServiceSeeder::class);

        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make(env('DEMO_USER_PASSWORD', 'password')),
                'email_verified_at' => now(),
            ]
        );

        $this->call(LineWattDemoUserSeeder::class);
        $this->call(LegalGovernanceSeeder::class);
    }
}
