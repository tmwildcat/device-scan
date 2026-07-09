<?php

use App\LineWatt\Access\LineWattRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default(LineWattRole::GUEST)->after('password')->index();
            $table->string('plan_code')->nullable()->after('role')->index();
            $table->string('subscription_status')->nullable()->after('plan_code')->index();
            $table->json('entitlement_overrides')->nullable()->after('subscription_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'role',
                'plan_code',
                'subscription_status',
                'entitlement_overrides',
            ]);
        });
    }
};
