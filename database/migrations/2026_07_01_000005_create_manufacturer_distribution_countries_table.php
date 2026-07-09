<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturer_distribution_countries', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('manufacturer_company_id')->constrained()->cascadeOnDelete();
            $table->string('country');
            $table->string('region')->nullable();
            $table->string('availability_status')->default('available');
            $table->string('channel_model')->nullable();
            $table->string('distributor_name')->nullable();
            $table->string('sales_contact')->nullable();
            $table->string('service_contact')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['manufacturer_company_id', 'availability_status']);
            $table->index(['country', 'region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manufacturer_distribution_countries');
    }
};
