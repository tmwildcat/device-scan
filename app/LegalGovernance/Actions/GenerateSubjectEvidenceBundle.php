<?php

namespace App\LegalGovernance\Actions;

use App\LegalGovernance\Models\LegalAcceptance;
use App\LegalGovernance\Models\LegalAuditEvent;
use App\LegalGovernance\Models\LegalObligation;
use App\LegalGovernance\Support\CanonicalJson;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class GenerateSubjectEvidenceBundle
{
    public function handle(string $actorType, string $actorId, string $exportedBy, ?string $caseReference = null): string
    {
        $acceptances = LegalAcceptance::query()->where('actor_type', $actorType)->where('actor_id', $actorId)->get();
        $obligations = LegalObligation::query()->where('actor_type', $actorType)->where('actor_id', $actorId)->get();
        $events = LegalAuditEvent::query()->where('actor_type', $actorType)->where('actor_id', $actorId)->orderBy('occurred_at')->get();
        $bundle = ['schema_version' => '1.0', 'identity' => ['type' => $actorType, 'id' => $actorId], 'generated_at' => now()->utc()->toIso8601String(), 'generated_by' => $exportedBy, 'acceptances' => $acceptances->map(fn ($a) => ['public_id' => $a->public_id, 'legal_document_version_id' => $a->legal_document_version_id, 'type' => $a->acceptance_type, 'status' => $a->status, 'accepted_at' => $a->accepted_at?->utc()->toIso8601String(), 'statement' => $a->acceptance_statement, 'manifest_checksum' => $a->manifest_checksum, 'evidence_checksum' => $a->evidence_checksum])->all(), 'outstanding' => $obligations->whereNotIn('status', ['completed', 'waived', 'cancelled', 'superseded'])->values()->toArray(), 'audit_events' => $events->map(fn ($e) => ['event_type' => $e->event_type, 'occurred_at' => $e->occurred_at?->utc()->toIso8601String(), 'summary' => $e->summary])->all()];
        $json = CanonicalJson::encode($bundle);
        $path = config('legal-governance.storage_prefix').'/evidence/'.Str::uuid().'/evidence.json';
        Storage::disk(config('legal-governance.storage_disk'))->put($path, $json);
        LegalAuditEvent::create(['public_id' => (string) Str::uuid(), 'event_type' => 'legal_evidence_exported', 'actor_type' => $actorType, 'actor_id' => $actorId, 'occurred_at' => now(), 'summary' => 'Subject legal evidence bundle exported.', 'metadata' => ['storage_path' => $path, 'checksum' => hash('sha256', $json), 'exported_by' => $exportedBy, 'case_reference' => $caseReference]]);

        return $path;
    }
}
