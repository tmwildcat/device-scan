<?php

namespace App\LegalGovernance\Services;

use App\LegalGovernance\Contracts\LegalAuditContract;
use App\LegalGovernance\Models\LegalAuditEvent;
use Illuminate\Support\Str;

final class LegalAuditService implements LegalAuditContract
{
    public function record(string $eventType, array $context): void
    {
        LegalAuditEvent::create(['public_id' => (string) Str::uuid(), 'event_type' => $eventType, 'occurred_at' => now(), 'summary' => $context['summary'] ?? $eventType, 'actor_type' => $context['actor_type'] ?? null, 'actor_id' => $context['actor_id'] ?? null, 'subject_type' => $context['subject_type'] ?? null, 'subject_id' => $context['subject_id'] ?? null, 'legal_document_id' => $context['legal_document_id'] ?? null, 'legal_document_version_id' => $context['legal_document_version_id'] ?? null, 'legal_workflow_id' => $context['legal_workflow_id'] ?? null, 'legal_acceptance_id' => $context['legal_acceptance_id'] ?? null, 'legal_manifest_id' => $context['legal_manifest_id'] ?? null, 'ip_address' => $context['ip_address'] ?? null, 'user_agent' => $context['user_agent'] ?? null, 'request_reference' => $context['request_reference'] ?? null, 'metadata' => $context['metadata'] ?? []]);
    }
}
