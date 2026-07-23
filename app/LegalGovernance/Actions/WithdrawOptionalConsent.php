<?php

namespace App\LegalGovernance\Actions;

use App\LegalGovernance\Models\LegalAcceptance;
use App\LegalGovernance\Services\LegalAuditService;
use App\LegalGovernance\Support\CanonicalJson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

final class WithdrawOptionalConsent
{
    public function __construct(private LegalAuditService $audit) {}

    public function handle(LegalAcceptance $original, string $actorId): LegalAcceptance
    {
        if (! in_array($original->acceptance_type, ['consent', 'optional_consent'], true) || $original->actor_id !== $actorId) {
            throw new LogicException('This legal action cannot be withdrawn by the actor.');
        }

        return DB::transaction(function () use ($original, $actorId): LegalAcceptance {
            $at = now()->utc();
            $evidence = ['action' => 'withdrawal', 'original_acceptance_public_id' => $original->public_id, 'withdrawn_at' => $at->toIso8601String()];
            $withdrawal = LegalAcceptance::create([
                'public_id' => (string) Str::uuid(), 'legal_document_version_id' => $original->legal_document_version_id,
                'legal_workflow_id' => $original->legal_workflow_id, 'actor_type' => $original->actor_type, 'actor_id' => $actorId,
                'subject_type' => $original->subject_type, 'subject_id' => $original->subject_id,
                'organisation_type' => $original->organisation_type, 'organisation_id' => $original->organisation_id,
                'acceptance_type' => $original->acceptance_type, 'status' => 'withdrawn', 'withdrawn_at' => $at,
                'acceptance_method' => 'withdrawal', 'acceptance_statement' => 'Consent withdrawn.', 'locale' => $original->locale,
                'presented_checksum' => $original->presented_checksum, 'manifest_checksum' => $original->manifest_checksum,
                'evidence_checksum' => hash('sha256', CanonicalJson::encode($evidence)), 'evidence' => $evidence, 'created_at' => $at,
            ]);
            $this->audit->record('legal_consent_withdrawn', ['actor_type' => $original->actor_type, 'actor_id' => $actorId, 'legal_acceptance_id' => $withdrawal->id, 'summary' => 'Consent withdrawal recorded as new evidence.']);

            return $withdrawal;
        });
    }
}
