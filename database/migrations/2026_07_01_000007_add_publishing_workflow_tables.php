<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_datasheets', function (Blueprint $table): void {
            $table->string('review_status')->nullable()->index()->after('status');
        });

        Schema::table('compiled_device_records', function (Blueprint $table): void {
            $table->string('review_status')->nullable()->index()->after('status');
        });

        Schema::create('review_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('compiled_device_record_id')->constrained('compiled_device_records')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->text('comment')->nullable();
            $table->string('previous_status')->nullable();
            $table->string('new_status')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('activities', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('event')->index();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->foreignId('compiled_device_record_id')->nullable()->constrained('compiled_device_records')->nullOnDelete();
            $table->foreignId('device_datasheet_id')->nullable()->constrained('device_datasheets')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('action_url')->nullable();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->string('channel')->index();
            $table->string('status')->default('pending')->index();
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('review_comments');

        Schema::table('compiled_device_records', function (Blueprint $table): void {
            $table->dropColumn('review_status');
        });

        Schema::table('device_datasheets', function (Blueprint $table): void {
            $table->dropColumn('review_status');
        });
    }
};
