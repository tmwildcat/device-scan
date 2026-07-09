<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manufacturer_companies', function (Blueprint $table): void {
            if (! Schema::hasColumn('manufacturer_companies', 'champion_id')) {
                $table->foreignId('champion_id')->nullable()->after('max_users')->constrained('library_champions')->nullOnDelete();
                $table->string('referral_code')->nullable()->after('champion_id')->index();
                $table->timestamp('referred_at')->nullable()->after('referral_code');
            }
        });

        if (Schema::hasTable('partner_requests')) {
            Schema::table('partner_requests', function (Blueprint $table): void {
                if (! Schema::hasColumn('partner_requests', 'champion_id')) {
                    $table->foreignId('champion_id')->nullable()->after('manufacturer_company_id')->constrained('library_champions')->nullOnDelete();
                    $table->string('referral_code')->nullable()->after('champion_id')->index();
                    $table->timestamp('referred_at')->nullable()->after('referral_code');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('partner_requests')) {
            Schema::table('partner_requests', function (Blueprint $table): void {
                if (Schema::hasColumn('partner_requests', 'champion_id')) {
                    $table->dropConstrainedForeignId('champion_id');
                    $table->dropColumn(['referral_code', 'referred_at']);
                }
            });
        }

        Schema::table('manufacturer_companies', function (Blueprint $table): void {
            if (Schema::hasColumn('manufacturer_companies', 'champion_id')) {
                $table->dropConstrainedForeignId('champion_id');
                $table->dropColumn(['referral_code', 'referred_at']);
            }
        });
    }
};
