<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturer_companies', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name')->index();
            $table->string('slug')->unique();
            $table->string('plan_code')->default('pro')->index();
            $table->string('subscription_status')->default('contract_active')->index();
            $table->unsignedInteger('max_users')->default(1);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('manufacturer_company_id')
                ->nullable()
                ->after('subscription_status')
                ->constrained('manufacturer_companies')
                ->nullOnDelete();
            $table->string('manufacturer_role')->nullable()->after('manufacturer_company_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('manufacturer_company_id');
            $table->dropColumn('manufacturer_role');
        });

        Schema::dropIfExists('manufacturer_companies');
    }
};
