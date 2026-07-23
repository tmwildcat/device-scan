<?php

namespace App\LegalGovernance\Actions;

use App\LegalGovernance\Enums\LegalVersionStatus;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Services\LegalAuditService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

final class TransitionLegalVersion
{
    public function __construct(private LegalAuditService $audit) {}

    public function submitForReview(LegalDocumentVersion $version, User $actor): LegalDocumentVersion
    {
        $this->authorize($actor, 'legal.versions.submit_review');

        return $this->move($version, [LegalVersionStatus::Draft, LegalVersionStatus::ChangesRequested], [
            'status' => 'in_review', 'submitted_at' => now(), 'submitted_for_review_by' => (string) $actor->id,
        ], $actor, 'legal_version_submitted_for_review');
    }

    public function returnToDraft(LegalDocumentVersion $version, User $actor, string $reason): LegalDocumentVersion
    {
        $this->authorize($actor, 'legal.versions.return_to_draft');
        if (blank($reason)) {
            throw new LogicException('A reason is required to return a version to Draft.');
        }

        return $this->move($version, [LegalVersionStatus::InReview, LegalVersionStatus::ChangesRequested, LegalVersionStatus::Approved], [
            'status' => 'draft', 'approved_at' => null, 'approved_by' => null, 'approved_checksum' => null,
            'approved_metadata' => null, 'scheduled_publish_at' => null, 'updated_by' => (string) $actor->id,
        ], $actor, 'legal_version_returned_to_draft', $reason);
    }

    public function approve(LegalDocumentVersion $version, User $actor, array $requiredReviewTypes = []): LegalDocumentVersion
    {
        $this->authorize($actor, 'legal.versions.approve');
        if ($version->status !== LegalVersionStatus::InReview) {
            throw new LogicException('Only an In Review version may be approved.');
        }
        foreach ($requiredReviewTypes as $type) {
            $valid = $version->reviews()->where('review_type', $type)->where('decision', 'approved')->where('reviewed_checksum', $version->content_checksum)->exists();
            if (! $valid) {
                throw new LogicException("Missing current {$type} approval.");
            }
        }

        return $this->move($version, [LegalVersionStatus::InReview], [
            'status' => 'approved', 'approved_at' => now(), 'approved_by' => (string) $actor->id,
            'approved_checksum' => $version->content_checksum, 'approved_metadata' => $version->metadata ?? [],
        ], $actor, 'legal_version_approved');
    }

    public function schedule(LegalDocumentVersion $version, User $actor, \DateTimeInterface $at): LegalDocumentVersion
    {
        $this->authorize($actor, 'legal.versions.schedule');
        if ($at <= now()) {
            throw new LogicException('Scheduled publication must be in the future.');
        }

        return $this->move($version, [LegalVersionStatus::Approved], [
            'status' => 'scheduled', 'scheduled_at' => now(), 'scheduled_publish_at' => $at,
            'scheduled_by' => (string) $actor->id, 'updated_by' => (string) $actor->id,
        ], $actor, 'legal_version_scheduled');
    }

    public function cancelSchedule(LegalDocumentVersion $version, User $actor, string $reason): LegalDocumentVersion
    {
        $this->authorize($actor, 'legal.versions.cancel_schedule');
        if (blank($reason)) {
            throw new LogicException('A cancellation reason is required.');
        }

        return $this->move($version, [LegalVersionStatus::Scheduled], [
            'status' => 'approved', 'schedule_cancelled_at' => now(), 'schedule_cancelled_by' => (string) $actor->id,
            'schedule_cancellation_reason' => $reason, 'updated_by' => (string) $actor->id,
        ], $actor, 'legal_publication_schedule_cancelled', $reason);
    }

    public function withdraw(LegalDocumentVersion $version, User $actor, string $reason): LegalDocumentVersion
    {
        $this->authorize($actor, 'legal.versions.withdraw');
        if (blank($reason)) {
            throw new LogicException('A withdrawal reason is required.');
        }
        if ($version->status !== LegalVersionStatus::Published) {
            throw new LogicException('Only a Published version may be withdrawn.');
        }

        return $this->directImmutableMove($version, 'withdrawn', [
            'withdrawn_at' => now(), 'withdrawn_by' => (string) $actor->id, 'withdrawal_reason' => $reason,
        ], $actor, 'legal_version_withdrawn', $reason);
    }

    public function archive(LegalDocumentVersion $version, User $actor, string $reason): LegalDocumentVersion
    {
        $this->authorize($actor, 'legal.versions.archive');
        if (blank($reason)) {
            throw new LogicException('An archival reason is required.');
        }
        if (! in_array($version->status, [LegalVersionStatus::Draft, LegalVersionStatus::ChangesRequested, LegalVersionStatus::Withdrawn, LegalVersionStatus::Superseded], true)) {
            throw new LogicException('Only obsolete Draft, Withdrawn, Changes Requested or Superseded versions may be archived.');
        }

        return $this->directImmutableMove($version, 'archived', [
            'archived_at' => now(), 'archived_by' => (string) $actor->id, 'archive_reason' => $reason,
        ], $actor, 'legal_version_archived', $reason);
    }

    private function move(LegalDocumentVersion $version, array $from, array $data, User $actor, string $event, ?string $reason = null): LegalDocumentVersion
    {
        if (! in_array($version->status, $from, true)) {
            throw new LogicException('Invalid legal lifecycle transition.');
        }

        return DB::transaction(function () use ($version, $data, $actor, $event, $reason) {
            $locked = LegalDocumentVersion::query()->lockForUpdate()->findOrFail($version->id);
            $previous = $locked->status->value;
            $locked->update($data);
            $this->audit($locked, $actor, $event, $previous, $locked->status->value, $reason);

            return $locked->refresh();
        });
    }

    private function directImmutableMove(LegalDocumentVersion $version, string $status, array $data, User $actor, string $event, string $reason): LegalDocumentVersion
    {
        return DB::transaction(function () use ($version, $status, $data, $actor, $event, $reason) {
            $locked = LegalDocumentVersion::query()->lockForUpdate()->findOrFail($version->id);
            $previous = $locked->status->value;
            DB::table('legal_document_versions')->where('id', $locked->id)->update([...$data, 'status' => $status, 'updated_by' => (string) $actor->id, 'updated_at' => now()]);
            $locked->refresh();
            $this->audit($locked, $actor, $event, $previous, $status, $reason);

            return $locked;
        });
    }

    private function authorize(User $actor, string $permission): void
    {
        abort_unless($actor->hasLegalPermission($permission), 403);
    }

    private function audit(LegalDocumentVersion $version, User $actor, string $event, string $from, string $to, ?string $reason): void
    {
        $this->audit->record($event, ['summary' => "Legal version moved from {$from} to {$to}.", 'actor_type' => User::class, 'actor_id' => (string) $actor->id, 'legal_document_id' => $version->legal_document_id, 'legal_document_version_id' => $version->id, 'metadata' => ['previous_state' => $from, 'new_state' => $to, 'authority_role' => $actor->role, 'reason' => $reason]]);
    }
}
