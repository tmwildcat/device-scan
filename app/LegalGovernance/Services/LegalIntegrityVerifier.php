<?php

namespace App\LegalGovernance\Services;

use App\LegalGovernance\Models\LegalAcceptance;
use App\LegalGovernance\Models\LegalArtifact;
use App\LegalGovernance\Models\LegalAuditEvent;
use App\LegalGovernance\Models\LegalManifest;
use App\LegalGovernance\Support\CanonicalJson;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class LegalIntegrityVerifier
{
    /** @return array{checked:int,discrepancies:list<array<string,string>>,duration_ms:int} */
    public function verify(string $actor = 'console'): array
    {
        $started = hrtime(true);
        $checked = 0;
        $discrepancies = [];

        foreach (LegalArtifact::query()->whereHas('version', fn ($query) => $query->whereIn('status', ['published', 'superseded', 'withdrawn']))->get() as $artifact) {
            $checked++;
            $disk = Storage::disk($artifact->storage_disk);
            if (! $disk->exists($artifact->storage_path)) {
                $discrepancies[] = ['type' => 'missing_artifact', 'reference' => (string) $artifact->id];

                continue;
            }
            if (! hash_equals($artifact->checksum, hash($artifact->checksum_algorithm, $disk->get($artifact->storage_path)))) {
                $discrepancies[] = ['type' => 'artifact_checksum', 'reference' => (string) $artifact->id];
            }
        }

        foreach (LegalManifest::query()->get() as $manifest) {
            $checked++;
            $actual = hash($manifest->checksum_algorithm, CanonicalJson::encode($manifest->canonical_json));
            if (! hash_equals($manifest->checksum, $actual)) {
                $discrepancies[] = ['type' => 'manifest_checksum', 'reference' => $manifest->public_id];
            }
        }

        foreach (LegalAcceptance::query()->get() as $acceptance) {
            $checked++;
            $actual = hash('sha256', CanonicalJson::encode($acceptance->evidence));
            if (! hash_equals($acceptance->evidence_checksum, $actual)) {
                $discrepancies[] = ['type' => 'acceptance_checksum', 'reference' => $acceptance->public_id];
            }
        }

        $duration = (int) round((hrtime(true) - $started) / 1_000_000);
        LegalAuditEvent::query()->create(['public_id' => (string) Str::uuid(), 'event_type' => 'legal_integrity_verified', 'actor_type' => 'system', 'actor_id' => $actor, 'occurred_at' => now(), 'summary' => $discrepancies === [] ? 'Legal integrity verification passed.' : 'Legal integrity verification found discrepancies.', 'metadata' => ['checked' => $checked, 'discrepancy_count' => count($discrepancies), 'discrepancies' => $discrepancies, 'duration_ms' => $duration]]);

        return ['checked' => $checked, 'discrepancies' => $discrepancies, 'duration_ms' => $duration];
    }
}
