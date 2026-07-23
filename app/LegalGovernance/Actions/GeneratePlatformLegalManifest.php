<?php

namespace App\LegalGovernance\Actions;

use App\LegalGovernance\Models\LegalAcceptance;
use App\LegalGovernance\Models\LegalDocument;
use App\LegalGovernance\Models\LegalManifest;
use App\LegalGovernance\Models\LegalObligation;
use App\LegalGovernance\Models\LegalWorkflow;
use App\LegalGovernance\Support\CanonicalJson;
use Illuminate\Support\Str;

final class GeneratePlatformLegalManifest
{
    public function handle(string $actor): LegalManifest
    {
        $data = ['schema_version' => '1.0', 'manifest_id' => (string) Str::uuid(), 'application_key' => config('legal-governance.application_key'), 'environment' => app()->environment(), 'generated_at' => now()->utc()->toIso8601String(), 'generated_by' => $actor, 'documents' => LegalDocument::query()->with(['versions' => fn ($q) => $q->whereIn('status', ['published', 'superseded', 'withdrawn'])])->orderBy('slug')->get()->map(fn ($d) => ['public_id' => $d->public_id, 'slug' => $d->slug, 'visibility' => $d->visibility, 'versions' => $d->versions->map(fn ($v) => ['public_id' => $v->public_id, 'label' => $v->version_label, 'status' => $v->status->value, 'checksum' => $v->content_checksum, 'effective_at' => $v->effective_at?->utc()->toIso8601String(), 'superseded_by_version_id' => $v->superseded_by_version_id])->all()])->all(), 'workflows' => LegalWorkflow::query()->select('public_id', 'slug', 'trigger_type', 'audience', 'status')->orderBy('slug')->get()->toArray(), 'acceptance_counts' => LegalAcceptance::query()->selectRaw('acceptance_type,count(*) as aggregate')->groupBy('acceptance_type')->pluck('aggregate', 'acceptance_type')->all(), 'outstanding_counts' => LegalObligation::query()->selectRaw('status,count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status')->all()];
        $json = CanonicalJson::encode($data);

        return LegalManifest::create(['public_id' => $data['manifest_id'], 'manifest_type' => 'platform_snapshot', 'schema_version' => '1.0', 'application_key' => config('legal-governance.application_key'), 'canonical_json' => json_decode($json, true, 512, JSON_THROW_ON_ERROR), 'checksum_algorithm' => 'sha256', 'checksum' => hash('sha256', $json), 'generated_at' => now(), 'generated_by' => $actor, 'metadata' => []]);
    }
}
