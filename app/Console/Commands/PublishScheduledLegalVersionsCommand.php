<?php

namespace App\Console\Commands;

use App\LegalGovernance\Actions\PublishLegalVersion;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Services\LegalAuditService;
use App\Models\User;
use Illuminate\Console\Command;
use Throwable;

final class PublishScheduledLegalVersionsCommand extends Command
{
    protected $signature = 'legal:publish-scheduled';

    protected $description = 'Revalidate and publish due scheduled legal versions';

    public function handle(PublishLegalVersion $publish, LegalAuditService $audit): int
    {
        $failed = 0;
        LegalDocumentVersion::query()->where('status', 'scheduled')->where('scheduled_publish_at', '<=', now())->each(function (LegalDocumentVersion $version) use ($publish, $audit, &$failed): void {
            try {
                $actor = User::query()->findOrFail($version->scheduled_by);
                $publish->handle($version, $actor);
                $audit->record('legal_scheduled_publication_executed', ['summary' => "Executed scheduled publication {$version->version_label}.", 'actor_type' => 'system', 'actor_id' => 'scheduler', 'legal_document_id' => $version->legal_document_id, 'legal_document_version_id' => $version->id, 'metadata' => ['scheduled_by' => $version->scheduled_by]]);
            } catch (Throwable $exception) {
                $failed++;
                $audit->record('legal_scheduled_publication_failed', ['summary' => "Scheduled publication failed: {$exception->getMessage()}", 'actor_type' => 'system', 'actor_id' => 'scheduler', 'legal_document_id' => $version->legal_document_id, 'legal_document_version_id' => $version->id, 'metadata' => ['scheduled_by' => $version->scheduled_by]]);
            }
        });

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
