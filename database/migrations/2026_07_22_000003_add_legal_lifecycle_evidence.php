<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_document_versions', function (Blueprint $table): void {
            $table->timestampTz('submitted_at')->nullable()->index();
            $table->timestampTz('approved_at')->nullable()->index();
            $table->char('approved_checksum', 64)->nullable();
            $table->json('approved_metadata')->nullable();
            $table->timestampTz('scheduled_at')->nullable();
            $table->string('scheduled_by')->nullable();
            $table->timestampTz('schedule_cancelled_at')->nullable();
            $table->string('schedule_cancelled_by')->nullable();
            $table->text('schedule_cancellation_reason')->nullable();
            $table->string('withdrawn_by')->nullable();
            $table->text('withdrawal_reason')->nullable();
            $table->timestampTz('archived_at')->nullable()->index();
            $table->string('archived_by')->nullable();
            $table->text('archive_reason')->nullable();
            $table->index(['status', 'scheduled_publish_at'], 'legal_version_schedule_queue');
        });
    }

    public function down(): void
    {
        Schema::table('legal_document_versions', function (Blueprint $table): void {
            $table->dropIndex('legal_version_schedule_queue');
            $table->dropColumn(['submitted_at', 'approved_at', 'approved_checksum', 'approved_metadata', 'scheduled_at', 'scheduled_by', 'schedule_cancelled_at', 'schedule_cancelled_by', 'schedule_cancellation_reason', 'withdrawn_by', 'withdrawal_reason', 'archived_at', 'archived_by', 'archive_reason']);
        });
    }
};
