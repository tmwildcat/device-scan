<?php

namespace App\LegalGovernance\Services;

use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Models\LegalManifest;
use App\LegalGovernance\Support\CanonicalJson;
use Illuminate\Support\Str;

final class LegalManifestService
{
    public function publication(LegalDocumentVersion $version, ?string $actor = null): LegalManifest
    {
        $version->loadMissing('document', 'artifacts');
        $data = ['application_key' => $version->document->application_key, 'document' => ['public_id' => $version->document->public_id, 'slug' => $version->document->slug, 'title' => $version->document->title, 'visibility' => $version->document->visibility], 'generated_at' => now()->utc()->toIso8601String(), 'schema_version' => '1.0', 'version' => ['public_id' => $version->public_id, 'label' => $version->version_label, 'checksum' => $version->content_checksum, 'effective_at' => $version->effective_at?->utc()->toIso8601String(), 'artifacts' => $version->artifacts->map(fn ($a) => ['type' => $a->artifact_type, 'checksum' => $a->checksum])->sortBy('type')->values()->all()]];
        $json = CanonicalJson::encode($data);

        return LegalManifest::create(['public_id' => (string) Str::uuid(), 'manifest_type' => 'publication', 'schema_version' => '1.0', 'application_key' => $version->document->application_key, 'canonical_json' => json_decode($json, true, 512, JSON_THROW_ON_ERROR), 'checksum_algorithm' => 'sha256', 'checksum' => hash('sha256', $json), 'generated_at' => now(), 'generated_by' => $actor, 'metadata' => ['legal_document_version_id' => $version->id]]);
    }

    public function acceptance(array $data, ?string $actor = null): LegalManifest
    {
        $json = CanonicalJson::encode($data);

        return LegalManifest::create(['public_id' => (string) Str::uuid(), 'manifest_type' => 'acceptance', 'schema_version' => '1.0', 'application_key' => config('legal-governance.application_key'), 'canonical_json' => json_decode($json, true, 512, JSON_THROW_ON_ERROR), 'checksum_algorithm' => 'sha256', 'checksum' => hash('sha256', $json), 'generated_at' => now(), 'generated_by' => $actor, 'metadata' => []]);
    }
}
