<?php

namespace App\LegalGovernance\Actions;

use App\LegalGovernance\Models\LegalAcceptance;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Models\LegalObligation;
use App\LegalGovernance\Models\LegalWorkflow;
use App\LegalGovernance\Services\LegalAuditService;
use App\LegalGovernance\Services\LegalManifestService;
use App\LegalGovernance\Support\CanonicalJson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class RecordLegalAcceptance
{
    public function __construct(private LegalManifestService $manifests, private LegalAuditService $audit) {}

    public function handle(LegalDocumentVersion $version, array $identity, string $type, string $statement, array $context = [], ?LegalWorkflow $workflow = null, ?LegalObligation $obligation = null): LegalAcceptance
    {
        return DB::transaction(function () use ($version, $identity, $type, $statement, $context, $workflow, $obligation) {
            if ($obligation) {
                $locked = LegalObligation::query()->lockForUpdate()->findOrFail($obligation->id);
                $existing = LegalAcceptance::query()->where('legal_obligation_id', $locked->id)->where('status', 'accepted')->first();
                if ($existing) {
                    return $existing;
                }
            }
            $at = now()->utc();
            $base = ['actor' => $identity, 'acceptance_type' => $type, 'acceptance_statement' => $statement, 'accepted_at' => $at->toIso8601String(), 'document_version_public_id' => $version->public_id, 'presented_checksum' => $version->content_checksum, 'workflow_public_id' => $workflow?->public_id, 'workflow_requirement_id' => $context['workflow_requirement_id'] ?? null, 'locale' => $context['locale'] ?? 'en', 'request_reference' => $context['request_reference'] ?? null];
            $manifest = $this->manifests->acceptance($base, $identity['id']);
            $evidence = [...$base, 'manifest_checksum' => $manifest->checksum];
            $acceptance = LegalAcceptance::create(['public_id' => (string) Str::uuid(), 'legal_document_version_id' => $version->id, 'legal_workflow_id' => $workflow?->id, 'legal_obligation_id' => $obligation?->id, 'actor_type' => $identity['type'], 'actor_id' => $identity['id'], 'subject_type' => $context['subject_type'] ?? null, 'subject_id' => $context['subject_id'] ?? null, 'organisation_type' => $identity['organisation_type'] ?? null, 'organisation_id' => $identity['organisation_id'] ?? null, 'acceptance_type' => $type, 'status' => $type === 'decline' ? 'declined' : 'accepted', 'accepted_at' => $type === 'decline' ? null : $at, 'declined_at' => $type === 'decline' ? $at : null, 'acceptance_method' => $context['method'] ?? 'web', 'acceptance_statement' => $statement, 'locale' => $context['locale'] ?? 'en', 'ip_address' => config('legal-governance.capture_ip') ? ($context['ip_address'] ?? null) : null, 'user_agent' => $context['user_agent'] ?? null, 'session_reference' => $context['session_reference'] ?? null, 'request_reference' => $context['request_reference'] ?? null, 'presented_checksum' => $version->content_checksum, 'manifest_checksum' => $manifest->checksum, 'evidence_checksum' => hash('sha256', CanonicalJson::encode($evidence)), 'evidence' => $evidence, 'created_at' => $at]);
            if ($obligation && $acceptance->status === 'accepted') {
                $obligation->update(['status' => 'completed', 'completed_at' => $at]);
            }$this->audit->record('legal_acceptance_recorded', ['actor_type' => $identity['type'], 'actor_id' => $identity['id'], 'legal_document_version_id' => $version->id, 'legal_workflow_id' => $workflow?->id, 'legal_acceptance_id' => $acceptance->id, 'summary' => 'Legal action evidence recorded.']);

            return $acceptance;
        });
    }
}
