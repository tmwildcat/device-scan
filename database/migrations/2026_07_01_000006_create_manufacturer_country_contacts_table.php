<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturer_country_contacts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('manufacturer_company_id')->constrained()->cascadeOnDelete();
            $table->string('country');
            $table->string('contact_type')->default('general');
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('region')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['manufacturer_company_id', 'country']);
            $table->index(['contact_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturer_country_contacts');
    }
};
