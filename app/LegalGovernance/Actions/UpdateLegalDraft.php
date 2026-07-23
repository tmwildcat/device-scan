<?php

namespace App\LegalGovernance\Actions;

use App\LegalGovernance\Enums\LegalVersionStatus;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Services\LegalContentRenderer;
use App\LegalGovernance\Services\LegalPlaceholderScanner;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

final class UpdateLegalDraft
{
    public function __construct(private LegalContentRenderer $renderer, private LegalPlaceholderScanner $scanner) {}

    public function handle(LegalDocumentVersion $version, string $markdown, string $summary, User $actor): LegalDocumentVersion
    {
        abort_unless($actor->hasLegalPermission('legal.documents.edit'), 403);
        if (! in_array($version->status, [LegalVersionStatus::Draft, LegalVersionStatus::ChangesRequested], true)) {
            throw new LogicException('Only editable Draft content can be changed.');
        }

        return DB::transaction(function () use ($version, $markdown, $summary, $actor) {
            $rendered = $this->renderer->render($markdown);
            $version->update(['markdown_source' => $markdown, 'sanitized_html' => $rendered['html'], 'plain_text' => $rendered['plain_text'], 'content_checksum' => $rendered['checksum'], 'change_summary' => $summary, 'updated_by' => (string) $actor->id, 'status' => 'draft']);
            $this->scanner->persist($version);

            return $version->refresh();
        });
    }
}
