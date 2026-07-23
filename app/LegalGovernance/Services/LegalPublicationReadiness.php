<?php

namespace App\LegalGovernance\Services;

use App\LegalGovernance\Models\LegalDocumentVersion;

final class LegalPublicationReadiness
{
    /** @return array<string, array{status:string,message:string}> */
    public function assess(LegalDocumentVersion $version): array
    {
        $version->loadMissing('document', 'placeholders', 'reviews');
        $required = $version->metadata['required_review_types'] ?? ['legal'];
        $reviewsReady = collect($required)->every(fn (string $type) => $version->reviews->contains(
            fn ($review) => $review->review_type === $type && $review->decision === 'approved' && hash_equals($review->reviewed_checksum, $version->content_checksum)
        ));
        $approvalReady = $version->approved_at && $version->approved_by && $version->approved_checksum
            && hash_equals($version->approved_checksum, $version->content_checksum)
            && ($version->approved_metadata ?? []) === ($version->metadata ?? []);

        return [
            'manifest_metadata' => $this->result((bool) ($version->document->title && $version->document->document_type && $version->document->visibility), 'Document manifest metadata is incomplete.'),
            'legal_review' => $this->result($reviewsReady, 'Current checksum lacks all required review approvals.'),
            'approval' => $this->result((bool) $approvalReady, 'Approval evidence does not match current content and metadata.'),
            'placeholders' => $this->result(! $version->placeholders->contains(fn ($item) => $item->release_blocking && $item->status === 'open'), 'Release-blocking placeholders remain.'),
            'effective_date' => $this->result((bool) $version->effective_at, 'An effective date is required.'),
            'version_identity' => $this->result((bool) $version->version_label, 'Version identity is missing.'),
            'acceptance_configuration' => $this->result(! $version->document->requires_acceptance_default || $version->document->metadata !== null, 'Acceptance configuration is incomplete.'),
            'material_change' => $this->result(! $version->is_material_change || array_key_exists('requires_reacceptance', $version->metadata ?? []), 'Material-change re-acceptance assessment is incomplete.'),
            'workflow_impact' => $this->result(true, 'Workflow impact is unresolved.'),
            'public_visibility' => $this->result($version->document->visibility !== 'public' || $version->document->is_active, 'Public document is inactive.'),
        ];
    }

    /** @return list<string> */
    public function failures(LegalDocumentVersion $version): array
    {
        return collect($this->assess($version))->filter(fn (array $item) => $item['status'] === 'blocked')->pluck('message')->values()->all();
    }

    private function result(bool $ready, string $message): array
    {
        return ['status' => $ready ? 'ready' : 'blocked', 'message' => $message];
    }
}
