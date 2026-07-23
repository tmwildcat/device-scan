<?php

namespace App\LegalGovernance\Actions;

use App\LegalGovernance\Enums\LegalVersionStatus;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Models\LegalReview;
use App\LegalGovernance\Services\LegalAuditService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

final class RecordLegalReview
{
    public function __construct(private LegalAuditService $audit) {}

    public function handle(LegalDocumentVersion $version, string $type, User $reviewer, string $decision, ?string $comments = null): LegalReview
    {
        abort_unless($reviewer->hasLegalPermission('legal.versions.review'), 403);
        if (! in_array($version->status, [LegalVersionStatus::InReview, LegalVersionStatus::ChangesRequested], true)) {
            throw new LogicException('Version is not in review.');
        }
        if (in_array($decision, ['changes_requested', 'rejected'], true) && blank($comments)) {
            throw new LogicException('Review comments are required when requesting changes or rejecting.');
        }

        return DB::transaction(function () use ($version, $type, $reviewer, $decision, $comments) {
            $review = LegalReview::create(['legal_document_version_id' => $version->id, 'review_type' => $type, 'reviewer_type' => User::class, 'reviewer_id' => (string) $reviewer->id, 'decision' => $decision, 'comments' => $comments, 'reviewed_checksum' => $version->content_checksum, 'reviewed_at' => now(), 'metadata' => ['authority_role' => $reviewer->role]]);
            if ($decision === 'changes_requested' || $decision === 'rejected') {
                $version->update(['status' => 'changes_requested']);
            }
            $this->audit->record('legal_review_decision_recorded', ['summary' => "Recorded {$type} review decision: {$decision}.", 'actor_type' => User::class, 'actor_id' => (string) $reviewer->id, 'legal_document_id' => $version->legal_document_id, 'legal_document_version_id' => $version->id, 'metadata' => ['decision' => $decision, 'reviewed_checksum' => $version->content_checksum, 'authority_role' => $reviewer->role]]);

            return $review;
        });
    }
}
