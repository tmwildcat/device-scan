<?php

namespace App\LegalGovernance\Actions;

use App\LegalGovernance\Enums\LegalVersionStatus;
use App\LegalGovernance\Models\LegalAcceptance;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Models\LegalObligation;
use App\LegalGovernance\Models\LegalWorkflowRequirement;
use App\LegalGovernance\Services\LegalArtifactService;
use App\LegalGovernance\Services\LegalAuditService;
use App\LegalGovernance\Services\LegalManifestService;
use App\LegalGovernance\Services\LegalPublicationReadiness;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use LogicException;

final class PublishLegalVersion
{
    public function __construct(private LegalArtifactService $artifacts, private LegalManifestService $manifests, private LegalAuditService $audit, private LegalPublicationReadiness $readiness) {}

    public function handle(LegalDocumentVersion $version, User $actor): LegalDocumentVersion
    {
        abort_unless($actor->hasLegalPermission('legal.versions.publish'), 403);

        return DB::transaction(function () use ($version, $actor) {
            $version = LegalDocumentVersion::query()->lockForUpdate()->findOrFail($version->id);
            $version->load('placeholders', 'document', 'reviews');
            if (! in_array($version->status, [LegalVersionStatus::Approved, LegalVersionStatus::Scheduled], true)) {
                throw new LogicException('Only an Approved or Scheduled version may be published.');
            }
            $failures = $this->readiness->failures($version);
            if ($failures !== []) {
                throw new LogicException(implode(' ', $failures));
            }
            $previousState = $version->status->value;
            $actorId = (string) $actor->id;
            $this->artifacts->generate($version, $actorId);
            $manifest = $this->manifests->publication($version, $actorId);
            $version->update(['status' => 'published', 'published_at' => now(), 'published_by' => $actorId]);
            $prior = $version->document->versions()->where('id', '!=', $version->id)->where('status', 'published')->latest('published_at')->first();
            if ($prior) {
                DB::table('legal_document_versions')->where('id', $prior->id)->update(['status' => 'superseded', 'superseded_at' => now(), 'superseded_by_version_id' => $version->id, 'updated_at' => now()]);
            }
            $obligations = $this->assignReacceptanceObligations($version);
            $this->audit->record('legal_version_published', ['summary' => "Published {$version->document->title} {$version->version_label}.", 'actor_type' => User::class, 'actor_id' => $actorId, 'legal_document_id' => $version->document->id, 'legal_document_version_id' => $version->id, 'legal_manifest_id' => $manifest->id, 'metadata' => ['previous_state' => $previousState, 'new_state' => 'published', 'authority_role' => $actor->role, 'readiness' => $this->readiness->assess($version)]]);
            if ($obligations > 0) {
                $this->audit->record('legal_reacceptance_obligations_assigned', ['summary' => "Assigned {$obligations} re-acceptance obligations for {$version->version_label}.", 'actor_type' => User::class, 'actor_id' => $actorId, 'legal_document_id' => $version->document->id, 'legal_document_version_id' => $version->id, 'metadata' => ['count' => $obligations]]);
            }

            return $version->refresh();
        });
    }

    private function assignReacceptanceObligations(LegalDocumentVersion $version): int
    {
        if (! $version->is_material_change || ! ($version->metadata['requires_reacceptance'] ?? false)) {
            return 0;
        }

        $count = 0;
        $requirements = LegalWorkflowRequirement::query()
            ->where('legal_document_id', $version->legal_document_id)
            ->whereHas('workflow', fn ($query) => $query->where('status', 'active'))
            ->with('workflow')
            ->get();

        foreach ($requirements as $requirement) {
            $priorAcceptances = LegalAcceptance::query()
                ->where('status', 'accepted')
                ->where('legal_workflow_id', $requirement->legal_workflow_id)
                ->whereHas('version', fn ($query) => $query->where('legal_document_id', $version->legal_document_id)->where('id', '!=', $version->id))
                ->get();
            foreach ($priorAcceptances as $acceptance) {
                $obligation = LegalObligation::firstOrCreate([
                    'legal_workflow_id' => $requirement->legal_workflow_id,
                    'legal_document_version_id' => $version->id,
                    'actor_type' => $acceptance->actor_type,
                    'actor_id' => $acceptance->actor_id,
                    'organisation_type' => $acceptance->organisation_type,
                    'organisation_id' => $acceptance->organisation_id,
                ], [
                    'subject_type' => $acceptance->subject_type,
                    'subject_id' => $acceptance->subject_id,
                    'status' => 'pending',
                    'required_at' => now(),
                    'due_at' => isset($requirement->configuration['grace_period_days']) ? now()->addDays((int) $requirement->configuration['grace_period_days']) : null,
                    'blocking_behavior' => $requirement->blocking_behavior,
                    'source_reference' => 'material_change_publication',
                    'metadata' => ['prior_acceptance_id' => $acceptance->public_id],
                ]);
                $count += $obligation->wasRecentlyCreated ? 1 : 0;
            }
        }

        return $count;
    }
}
