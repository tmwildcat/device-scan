<?php

namespace App\Http\Controllers\LegalGovernance;

use App\Http\Controllers\Controller;
use App\LegalGovernance\Actions\UpdateLegalDraft;
use App\LegalGovernance\Models\LegalAcceptance;
use App\LegalGovernance\Models\LegalAuditEvent;
use App\LegalGovernance\Models\LegalDocument;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Models\LegalManifest;
use App\LegalGovernance\Models\LegalObligation;
use App\LegalGovernance\Queries\LegalGovernanceDashboardQuery;
use App\LineWatt\Access\LineWattRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class LegalCounselController extends Controller
{
    public function dashboard(Request $request, LegalGovernanceDashboardQuery $dashboard): Response
    {
        $user = $request->user();

        return Inertia::render('LineWatt/LegalGovernanceDashboard', [
            'workspace' => [
                'name' => 'Legal Governance',
                'role_label' => LineWattRole::label($user?->role),
                'is_super_admin' => $user?->role === LineWattRole::SUPER_ADMIN,
                'navigation' => collect([
                    ['label' => 'Dashboard', 'route' => 'legal-governance.dashboard'],
                    ['label' => 'Documents', 'route' => 'legal-governance.documents'],
                    ['label' => 'Review Queue', 'route' => 'legal-governance.reviews.index'],
                    ['label' => 'Publication Schedule', 'route' => 'legal-governance.publications.index'],
                    ['label' => 'Workflows', 'route' => 'legal-governance.workflows.index'],
                    ['label' => 'Outstanding Obligations', 'route' => 'legal-governance.section', 'parameter' => 'obligations'],
                    ['label' => 'Acceptance Register', 'route' => 'legal-governance.section', 'parameter' => 'acceptances'],
                    ['label' => 'Manifests', 'route' => 'legal-governance.section', 'parameter' => 'manifests'],
                    ['label' => 'Evidence Exports', 'route' => 'legal-governance.evidence-exports.index'],
                    ['label' => 'Placeholders', 'route' => 'legal-governance.placeholders.index'],
                    ['label' => 'Audit Log', 'route' => 'legal-governance.section', 'parameter' => 'audit'],
                    ['label' => 'Settings', 'route' => 'legal-governance.settings'],
                ])->filter(fn (array $item) => match ($item['route']) {
                    'legal-governance.workflows.index' => $user?->hasLegalPermission('legal.workflows.view'),
                    'legal-governance.evidence-exports.index' => $user?->hasLegalPermission('legal.acceptances.export'),
                    'legal-governance.placeholders.index' => $user?->hasLegalPermission('legal.placeholders.view'),
                    'legal-governance.settings' => $user?->hasLegalPermission('legal.settings.manage'),
                    'legal-governance.section' => $user?->role !== LineWattRole::LEGAL_PUBLISHER,
                    default => true,
                })->values()->all(),
            ],
            'dashboard' => $dashboard->get(),
        ]);
    }

    public function documents(Request $request): Response
    {
        $status = (string) $request->string('status');
        $documents = LegalDocument::query()
            ->when($status, fn ($query) => $query->whereHas('versions', fn ($versions) => $versions->where('status', $status)))
            ->with(['versions' => fn ($query) => $query->when($status, fn ($versions) => $versions->where('status', $status))->latest('created_at')])
            ->withCount('versions')
            ->orderBy('title')
            ->paginate(30)
            ->through(fn (LegalDocument $document): array => [
                'id' => $document->public_id,
                'title' => $document->title,
                'type' => $document->document_type,
                'visibility' => $document->visibility,
                'versions_count' => $document->versions_count,
                'versions' => $document->versions->map(fn (LegalDocumentVersion $version): array => [
                    'id' => $version->public_id,
                    'label' => $version->version_label,
                    'status' => $version->status->value,
                    'updated_at' => $version->updated_at?->toIso8601String(),
                    'href' => route('legal-governance.versions.edit', $version, false),
                ])->all(),
            ]);

        return Inertia::render('LineWatt/LegalGovernanceDocuments', [
            'workspace' => $this->workspace($request),
            'documents' => $documents,
            'status_filter' => $status ?: null,
        ]);
    }

    public function edit(Request $request, LegalDocumentVersion $version): Response
    {
        $version->load('document', 'placeholders', 'reviews', 'artifacts');

        return Inertia::render('LineWatt/LegalGovernanceVersionEdit', [
            'workspace' => $this->workspace($request),
            'version' => [
                'id' => $version->public_id,
                'document_title' => $version->document->title,
                'version_label' => $version->version_label,
                'status' => $version->status->value,
                'editable' => in_array($version->status->value, ['draft', 'changes_requested'], true),
                'content_checksum' => $version->content_checksum,
                'change_summary' => $version->change_summary,
                'markdown_source' => $version->markdown_source,
                'sanitized_html' => $version->sanitized_html,
                'plain_text' => $version->plain_text,
                'placeholders_count' => $version->placeholders->where('status', 'open')->count(),
                'reviews_count' => $version->reviews->count(),
                'artifacts_count' => $version->artifacts->count(),
                'update_href' => route('legal-governance.versions.update', $version, false),
            ],
        ]);
    }

    public function update(Request $request, LegalDocumentVersion $version, UpdateLegalDraft $action): RedirectResponse
    {
        $data = $request->validate(['markdown_source' => 'required|string', 'change_summary' => 'required|string|max:2000']);
        $action->handle($version, $data['markdown_source'], $data['change_summary'], $request->user());

        return back()->with('status', 'Draft saved; prior approvals require renewal.');
    }

    public function section(Request $request, string $section): Response
    {
        abort_unless(in_array($section, ['versions', 'review-queue', 'publication-schedule', 'workflows', 'obligations', 'acceptances', 'manifests', 'evidence-exports', 'placeholders', 'audit', 'settings'], true), 404);
        [$columns, $rows] = match ($section) {
            'obligations' => [['Reference', 'Status', 'Required', 'Due'], LegalObligation::query()->latest()->paginate(50)->through(fn (LegalObligation $item) => [$item->public_id, $item->status, $item->required_at?->toIso8601String(), $item->due_at?->toIso8601String()])],
            'acceptances' => [['Reference', 'Action', 'Status', 'Recorded'], LegalAcceptance::query()->latest('created_at')->paginate(50)->through(fn (LegalAcceptance $item) => [$item->public_id, $item->acceptance_type, $item->status, $item->created_at?->toIso8601String()])],
            'manifests' => [['Reference', 'Type', 'Checksum', 'Generated'], LegalManifest::query()->latest('created_at')->paginate(50)->through(fn (LegalManifest $item) => [$item->public_id, $item->manifest_type, $item->checksum, $item->generated_at?->toIso8601String()])],
            'audit' => [['Event', 'Summary', 'Occurred'], LegalAuditEvent::query()->latest('occurred_at')->paginate(50)->through(fn (LegalAuditEvent $item) => [$item->event_type, $item->summary, $item->occurred_at?->toIso8601String()])],
            default => [[], collect()],
        };

        return Inertia::render('LineWatt/LegalGovernanceRegister', [
            'workspace' => $this->workspace($request),
            'title' => str($section)->replace('-', ' ')->title()->toString(),
            'columns' => $columns,
            'rows' => $rows,
        ]);
    }

    /** @return array<string, mixed> */
    private function workspace(Request $request): array
    {
        return [
            'role_label' => LineWattRole::label($request->user()?->role),
            'is_super_admin' => $request->user()?->role === LineWattRole::SUPER_ADMIN,
            'can_review' => (bool) $request->user()?->hasLegalPermission('legal.versions.review'),
            'can_approve' => (bool) $request->user()?->hasLegalPermission('legal.versions.approve'),
            'can_publish' => (bool) $request->user()?->hasLegalPermission('legal.versions.publish'),
            'can_manage_workflows' => (bool) $request->user()?->hasLegalPermission('legal.workflows.edit'),
        ];
    }
}
