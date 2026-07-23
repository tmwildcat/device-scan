<?php

namespace App\LegalGovernance\Actions;

use App\LegalGovernance\Models\LegalObligation;
use App\LegalGovernance\Models\LegalWorkflow;
use App\LegalGovernance\Services\LegalAuditService;
use App\LegalGovernance\Services\LegalWorkflowService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AssignLegalObligations
{
    public function __construct(private LegalWorkflowService $workflows, private LegalAuditService $audit) {}

    public function handle(LegalWorkflow $workflow, array $identity, ?string $source = null): array
    {
        return DB::transaction(function () use ($workflow, $identity, $source) {
            $out = [];
            foreach ($this->workflows->requirements($workflow) as $resolved) {
                $v = $resolved['version'];
                $r = $resolved['requirement'];
                $obligation = LegalObligation::query()->firstOrCreate(['legal_workflow_id' => $workflow->id, 'legal_document_version_id' => $v->id, 'actor_type' => $identity['type'], 'actor_id' => $identity['id'], 'organisation_type' => $identity['organisation_type'] ?? null, 'organisation_id' => $identity['organisation_id'] ?? null], ['public_id' => (string) Str::uuid(), 'status' => 'pending', 'required_at' => now(), 'due_at' => null, 'blocking_behavior' => $r->blocking_behavior, 'source_reference' => $source, 'metadata' => ['workflow_requirement_id' => $r->id]]);
                $out[] = $obligation;
                if ($obligation->wasRecentlyCreated) {
                    $this->audit->record('legal_obligation_created', ['actor_type' => $identity['type'], 'actor_id' => $identity['id'], 'legal_document_version_id' => $v->id, 'legal_workflow_id' => $workflow->id, 'summary' => 'A legal obligation was assigned.', 'metadata' => ['obligation_public_id' => $obligation->public_id, 'source' => $source]]);
                }
            }

            return $out;
        });
    }
}
