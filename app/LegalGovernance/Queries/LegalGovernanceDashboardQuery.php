<?php

namespace App\LegalGovernance\Queries;

use App\LegalGovernance\Models\LegalAcceptance;
use App\LegalGovernance\Models\LegalAuditEvent;
use App\LegalGovernance\Models\LegalDocument;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Models\LegalManifest;
use App\LegalGovernance\Models\LegalObligation;
use App\LegalGovernance\Models\LegalPlaceholder;
use Illuminate\Support\Facades\Schema;

final class LegalGovernanceDashboardQuery
{
    /** @return array<string, mixed> */
    public function get(): array
    {
        if (! Schema::hasTable('legal_document_versions')) {
            return $this->emptyState();
        }

        $statuses = LegalDocumentVersion::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count)
            ->all();

        return [
            'metrics' => [
                'documents' => LegalDocument::query()->count(),
                'drafts' => (int) ($statuses['draft'] ?? 0),
                'in_review' => (int) ($statuses['in_review'] ?? 0),
                'published' => (int) ($statuses['published'] ?? 0),
                'scheduled' => (int) ($statuses['scheduled'] ?? 0),
                'release_blockers' => LegalDocumentVersion::query()->whereHas(
                    'placeholders',
                    fn ($query) => $query->where('status', 'open')->where('release_blocking', true),
                )->count(),
                'outstanding_obligations' => Schema::hasTable('legal_obligations')
                    ? LegalObligation::query()->whereIn('status', ['pending', 'presented', 'overdue'])->count()
                    : 0,
                'acceptances' => Schema::hasTable('legal_acceptances') ? LegalAcceptance::query()->count() : 0,
                'manifests' => Schema::hasTable('legal_manifests') ? LegalManifest::query()->count() : 0,
            ],
            'status_counts' => $statuses,
            'record_counts' => [
                'versions' => LegalDocumentVersion::query()->count(),
                'acceptances' => Schema::hasTable('legal_acceptances') ? LegalAcceptance::query()->count() : 0,
                'manifests' => Schema::hasTable('legal_manifests') ? LegalManifest::query()->count() : 0,
                'open_placeholders' => Schema::hasTable('legal_placeholders') ? LegalPlaceholder::query()->where('status', 'open')->count() : 0,
            ],
            'recent_activity' => Schema::hasTable('legal_audit_events')
                ? LegalAuditEvent::query()->latest('occurred_at')->limit(10)->get([
                    'public_id', 'event_type', 'summary', 'occurred_at',
                ])->map(fn (LegalAuditEvent $event): array => [
                    'id' => $event->public_id,
                    'type' => $event->event_type,
                    'summary' => $event->summary,
                    'occurred_at' => $event->occurred_at?->toIso8601String(),
                ])->all()
                : [],
            'integrity' => $this->integrity(),
        ];
    }

    /** @return array<string, mixed> */
    private function emptyState(): array
    {
        return [
            'metrics' => ['documents' => 0, 'drafts' => 0, 'in_review' => 0, 'published' => 0, 'scheduled' => 0, 'release_blockers' => 0, 'outstanding_obligations' => 0, 'acceptances' => 0, 'manifests' => 0],
            'status_counts' => [],
            'record_counts' => ['versions' => 0, 'acceptances' => 0, 'manifests' => 0, 'open_placeholders' => 0],
            'recent_activity' => [],
            'integrity' => ['status' => 'not_run', 'last_run' => null, 'discrepancies' => null],
        ];
    }

    /** @return array{status:string,last_run:?string,discrepancies:?int} */
    private function integrity(): array
    {
        if (! Schema::hasTable('legal_audit_events')) {
            return ['status' => 'not_run', 'last_run' => null, 'discrepancies' => null];
        }
        $event = LegalAuditEvent::query()->where('event_type', 'legal_integrity_verified')->latest('occurred_at')->first();
        if (! $event) {
            return ['status' => 'not_run', 'last_run' => null, 'discrepancies' => null];
        }
        $count = (int) ($event->metadata['discrepancy_count'] ?? 0);
        $stale = $event->occurred_at->lt(now()->subHours((int) config('legal-governance.integrity_stale_hours', 48)));

        return ['status' => $count > 0 ? 'failed' : ($stale ? 'stale' : 'passed'), 'last_run' => $event->occurred_at->toIso8601String(), 'discrepancies' => $count];
    }
}
