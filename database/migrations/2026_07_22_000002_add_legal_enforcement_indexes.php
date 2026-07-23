<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_acceptances', function (Blueprint $table): void {
            $table->index(['actor_type', 'actor_id', 'legal_workflow_id', 'legal_document_version_id', 'status'], 'legal_acceptance_resolution');
            $table->unique('legal_obligation_id', 'legal_acceptance_obligation_unique');
        });
        Schema::table('legal_obligations', function (Blueprint $table): void {
            $table->index(['legal_workflow_id', 'actor_type', 'actor_id', 'status'], 'legal_obligation_access');
        });
    }

    public function down(): void
    {
        Schema::table('legal_acceptances', function (Blueprint $table): void {
            $table->dropIndex('legal_acceptance_resolution');
            $table->dropUnique('legal_acceptance_obligation_unique');
        });
        Schema::table('legal_obligations', fn (Blueprint $table) => $table->dropIndex('legal_obligation_access'));
    }
};
