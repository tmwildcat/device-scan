<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_datasheets', function (Blueprint $table): void {
            $table->string('pdf_access_mode')->default('internal_only')->index()->after('compiler_version');
            $table->text('source_url')->nullable()->after('pdf_access_mode');
            $table->string('source_domain')->nullable()->index()->after('source_url');
            $table->string('permission_status')->default('unknown')->index()->after('source_domain');
            $table->text('permission_notes')->nullable()->after('permission_status');
            $table->text('attribution_text')->nullable()->after('permission_notes');
            $table->boolean('can_public_download')->default(false)->after('attribution_text');
            $table->boolean('can_public_preview')->default(false)->after('can_public_download');
            $table->boolean('can_internal_preview')->default(true)->after('can_public_preview');
            $table->boolean('can_private_download')->default(true)->after('can_internal_preview');
        });
    }

    public function down(): void
    {
        Schema::table('device_datasheets', function (Blueprint $table): void {
            $table->dropColumn([
                'pdf_access_mode',
                'source_url',
                'source_domain',
                'permission_status',
                'permission_notes',
                'attribution_text',
                'can_public_download',
                'can_public_preview',
                'can_internal_preview',
                'can_private_download',
            ]);
        });
    }
};
