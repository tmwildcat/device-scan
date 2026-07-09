<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('status')->default('pending')->index();
            $table->string('company_name')->index();
            $table->string('website')->nullable();
            $table->string('country')->nullable()->index();
            $table->string('contact_person');
            $table->string('contact_email')->index();
            $table->string('official_email_domain')->nullable()->index();
            $table->string('requested_manufacturer_brand')->nullable()->index();
            $table->text('proof_notes')->nullable();
            $table->foreignId('manufacturer_company_id')->nullable()->constrained('manufacturer_companies')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_requests');
    }
};
