<?php

namespace App\Http\Controllers\LegalGovernance;

use App\Http\Controllers\Controller;
use App\LegalGovernance\Actions\GenerateSubjectEvidenceBundle;
use App\LegalGovernance\Actions\PublishLegalVersion;
use App\LegalGovernance\Actions\RecordLegalReview;
use App\LegalGovernance\Actions\TransitionLegalVersion;
use App\LegalGovernance\Models\LegalDocument;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Models\LegalPlaceholder;
use App\LegalGovernance\Models\LegalWorkflow;
use App\LegalGovernance\Models\LegalWorkflowRequirement;
use App\LegalGovernance\Services\LegalPublicationReadiness;
use App\LegalGovernance\Services\LegalWorkflowService;
use App\LineWatt\Access\LineWattRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LegalOperationsController extends Controller
{
    public function reviews(Request $request): Response
    {
        $query = LegalDocumentVersion::query()->with('document', 'placeholders', 'reviews')->whereIn('status', ['in_review', 'changes_requested']);
        foreach (['status', 'category'] as $filter) {
            if ($request->filled($filter)) {
                $filter === 'category'
                    ? $query->whereHas('document', fn ($q) => $q->where('category', (string) $request->string($filter)))
                    : $query->where($filter, (string) $request->string($filter));
            }
        }
        if ($request->filled('review_type')) {
            $query->whereJsonContains('metadata->required_review_types', (string) $request->string('review_type'));
        }
        if ($request->filled('reviewer')) {
            $query->whereHas('reviews', fn ($q) => $q->where('reviewer_id', (string) $request->string('reviewer')));
        }
        if ($request->filled('submitted_from')) {
            $query->whereDate('updated_at', '>=', $request->date('submitted_from'));
        }
        if ($request->filled('submitted_until')) {
            $query->whereDate('updated_at', '<=', $request->date('submitted_until'));
        }
        if ($request->filled('placeholder_state')) {
            $request->string('placeholder_state')->toString() === 'blocked'
                ? $query->whereHas('placeholders', fn ($q) => $q->where('status', 'open')->where('release_blocking', true))
                : $query->whereDoesntHave('placeholders', fn ($q) => $q->where('status', 'open')->where('release_blocking', true));
        }

        return $this->page($request, 'Review Queue', 'reviews', [
            'items' => $query->latest('updated_at')->paginate(30)->withQueryString()->through(fn (LegalDocumentVersion $version) => $this->versionSummary($version)),
            'filters' => $request->only('status', 'category', 'review_type', 'reviewer', 'submitted_from', 'submitted_until', 'placeholder_state'),
        ]);
    }

    public function review(Request $request, LegalDocumentVersion $version): Response
    {
        $version->load('document', 'placeholders', 'reviews');
        $previous = $version->document->versions()->where('id', '!=', $version->id)->where('created_at', '<=', $version->created_at)->latest('created_at')->first();

        return $this->page($request, 'Review '.$version->document->title, 'review-detail', [
            'version' => [...$this->versionSummary($version), 'markdown_source' => $version->markdown_source, 'sanitized_html' => $version->sanitized_html, 'plain_text' => $version->plain_text],
            'previous_version' => $previous ? ['version' => $previous->version_label, 'checksum' => $previous->content_checksum, 'markdown_source' => $previous->markdown_source] : null,
            'text_diff' => $previous ? $this->textDiff($previous->markdown_source, $version->markdown_source) : [],
            'required_reviews' => $version->metadata['required_review_types'] ?? ['legal'],
        ]);
    }

    public function submitReview(Request $request, LegalDocumentVersion $version, TransitionLegalVersion $transition): RedirectResponse
    {
        $transition->submitForReview($version, $request->user());

        return back()->with('success', 'Version submitted for checksum-bound review.');
    }

    public function decision(Request $request, LegalDocumentVersion $version, RecordLegalReview $reviews): RedirectResponse
    {
        $data = $request->validate([
            'review_type' => ['required', Rule::in(['legal', 'privacy', 'security', 'product', 'finance', 'engineering'])],
            'decision' => ['required', Rule::in(['approved', 'changes_requested', 'rejected'])],
            'comments' => ['nullable', 'string', 'max:5000'],
        ]);
        $reviews->handle($version, $data['review_type'], $request->user(), $data['decision'], $data['comments'] ?? null);

        return back()->with('success', 'Review decision recorded against the current checksum.');
    }

    public function approve(Request $request, LegalDocumentVersion $version, TransitionLegalVersion $transition): RedirectResponse
    {
        $required = $version->metadata['required_review_types'] ?? ['legal'];
        $transition->approve($version, $request->user(), $required);

        return back()->with('success', 'Version approved for publication preparation.');
    }

    public function publications(Request $request, LegalPublicationReadiness $readiness): Response
    {
        $versions = LegalDocumentVersion::query()->with('document', 'placeholders', 'reviews', 'artifacts')
            ->whereIn('status', ['approved', 'scheduled'])->latest('updated_at')->paginate(30)
            ->through(fn (LegalDocumentVersion $version) => [...$this->versionSummary($version), 'readiness' => collect($readiness->assess($version))->map->get('status')->all()]);

        return $this->page($request, 'Publication Schedule', 'publications', ['items' => $versions]);
    }

    public function schedule(Request $request, LegalDocumentVersion $version, TransitionLegalVersion $transition): RedirectResponse
    {
        $data = $request->validate(['publish_at' => ['required', 'date', 'after:now'], 'effective_at' => ['required', 'date', 'after_or_equal:publish_at']]);
        $version->update(['effective_at' => $data['effective_at']]);
        $transition->schedule($version, $request->user(), new \DateTimeImmutable($data['publish_at']));

        return back()->with('success', 'Publication scheduled. No legal approval status was changed.');
    }

    public function publish(Request $request, LegalDocumentVersion $version, PublishLegalVersion $publish): RedirectResponse
    {
        $publish->handle($version, $request->user());

        return back()->with('success', 'Version published with frozen artifacts and manifest.');
    }

    public function cancelSchedule(Request $request, LegalDocumentVersion $version, TransitionLegalVersion $transition): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:5000']]);
        $transition->cancelSchedule($version, $request->user(), $data['reason']);

        return back()->with('success', 'Publication schedule cancelled; version returned to Approved.');
    }

    public function withdraw(Request $request, LegalDocumentVersion $version, TransitionLegalVersion $transition): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:5000']]);
        $transition->withdraw($version, $request->user(), $data['reason']);

        return back()->with('success', 'Version withdrawn without deleting historical evidence.');
    }

    public function archive(Request $request, LegalDocumentVersion $version, TransitionLegalVersion $transition): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:5000']]);
        $transition->archive($version, $request->user(), $data['reason']);

        return back()->with('success', 'Non-operative Draft archived.');
    }

    public function returnToDraft(Request $request, LegalDocumentVersion $version, TransitionLegalVersion $transition): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:5000']]);
        $transition->returnToDraft($version, $request->user(), $data['reason']);

        return back()->with('success', 'Version returned to Draft; prior approval evidence was invalidated.');
    }

    public function workflows(Request $request, LegalWorkflowService $service): Response
    {
        $items = LegalWorkflow::query()->with('requirements.document')->latest()->paginate(30)->through(fn (LegalWorkflow $workflow) => [
            'id' => $workflow->public_id, 'name' => $workflow->name, 'trigger' => $workflow->trigger_type, 'audience' => $workflow->audience,
            'status' => $workflow->status, 'blocking_behavior' => $workflow->blocking_behavior, 'requirements' => $workflow->requirements->pluck('document.title')->all(),
            'validation_errors' => $service->validate($workflow), 'updated_at' => $workflow->updated_at?->toIso8601String(),
        ]);

        return $this->page($request, 'Workflows', 'workflows', ['items' => $items, 'supported_triggers' => config('legal-governance.supported_triggers')]);
    }

    public function workflow(Request $request, LegalWorkflow $workflow, LegalWorkflowService $service): Response
    {
        $workflow->load('requirements.document');

        return $this->page($request, 'Workflow: '.$workflow->name, 'workflow-detail', [
            'workflow' => $workflow->only(['public_id', 'name', 'description', 'trigger_type', 'audience', 'status', 'priority', 'blocking_behavior', 'effective_from', 'effective_until', 'configuration']),
            'requirements' => $workflow->requirements->map(fn ($item) => ['id' => $item->id, 'document_id' => $item->document->public_id, 'document' => $item->document->title, 'sequence' => $item->sequence, 'version_rule' => $item->version_selection_rule, 'specific_version' => $item->specific_version, 'acceptance_type' => $item->acceptance_type, 'required' => $item->is_required, 'blocking_behavior' => $item->blocking_behavior, 'statement' => $item->configuration['statement'] ?? null]),
            'validation_errors' => $service->validate($workflow), 'documents' => LegalDocument::query()->orderBy('title')->get(['public_id', 'title', 'visibility']),
        ]);
    }

    public function updateWorkflow(Request $request, LegalWorkflow $workflow): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:2000'],
            'trigger_type' => ['required', Rule::in(config('legal-governance.supported_triggers'))], 'audience' => ['required', 'string', 'max:100'],
            'blocking_behavior' => ['required', Rule::in(['notice_only', 'next_login_block', 'checkout_block', 'feature_block', 'credential_block', 'organisation_admin_required'])],
            'priority' => ['required', 'integer', 'between:-1000,1000'],
        ]);
        $workflow->update([...$data, 'updated_by' => (string) $request->user()->id]);

        return back()->with('success', 'Workflow configuration saved.');
    }

    public function activateWorkflow(Request $request, LegalWorkflow $workflow, LegalWorkflowService $service): RedirectResponse
    {
        $errors = $service->validate($workflow);
        abort_if($errors !== [], 422, implode(' ', $errors));
        $workflow->update(['status' => 'active', 'updated_by' => (string) $request->user()->id]);

        return back()->with('success', 'Validated workflow activated.');
    }

    public function storeWorkflowRequirement(Request $request, LegalWorkflow $workflow): RedirectResponse
    {
        $data = $this->validateRequirement($request);
        $document = LegalDocument::where('public_id', $data['document_id'])->firstOrFail();
        LegalWorkflowRequirement::updateOrCreate(
            ['legal_workflow_id' => $workflow->id, 'legal_document_id' => $document->id],
            ['sequence' => $data['sequence'], 'version_selection_rule' => $data['version_selection_rule'], 'specific_version' => $data['specific_version'] ?? null, 'acceptance_type' => $data['acceptance_type'], 'is_required' => $data['is_required'], 'blocking_behavior' => $data['blocking_behavior'], 'configuration' => ['statement' => $data['statement']]]
        );
        $workflow->update(['status' => 'draft', 'updated_by' => (string) $request->user()->id]);

        return back()->with('success', 'Workflow requirement saved and workflow returned to Draft for validation.');
    }

    public function destroyWorkflowRequirement(Request $request, LegalWorkflow $workflow, LegalWorkflowRequirement $requirement): RedirectResponse
    {
        abort_unless($requirement->legal_workflow_id === $workflow->id, 404);
        $requirement->delete();
        $workflow->update(['status' => 'draft', 'updated_by' => (string) $request->user()->id]);

        return back()->with('success', 'Workflow requirement removed.');
    }

    public function evidence(Request $request): Response
    {
        return $this->page($request, 'Evidence Exports', 'evidence', ['sensitive_access' => $request->user()->hasLegalPermission('legal.acceptances.view_sensitive'), 'csrf_token' => csrf_token()]);
    }

    public function exportEvidence(Request $request, GenerateSubjectEvidenceBundle $export): StreamedResponse
    {
        $data = $request->validate(['subject_type' => ['required', Rule::in(['user', 'manufacturer', 'organisation', 'enterprise_customer', 'api_client', 'mcp_client'])], 'subject_id' => ['required', 'string', 'max:255'], 'case_reference' => ['required', 'string', 'max:255'], 'confirmed' => ['accepted']]);
        $path = $export->handle($data['subject_type'], $data['subject_id'], (string) $request->user()->id, $data['case_reference']);

        return Storage::disk(config('legal-governance.storage_disk'))->download($path, 'legal-evidence-'.str($data['case_reference'])->slug().'.json');
    }

    public function placeholders(Request $request): Response
    {
        $items = LegalPlaceholder::query()->with('version.document')->latest()->paginate(50)->through(fn (LegalPlaceholder $item) => [
            'id' => $item->id, 'document' => $item->version->document->title, 'version' => $item->version->version_label, 'version_status' => $item->version->status->value,
            'placeholder' => $item->placeholder, 'context' => $item->context, 'severity' => $item->severity, 'release_blocking' => $item->release_blocking,
            'owner' => $item->assigned_owner, 'status' => $item->status, 'resolution' => $item->resolution,
        ]);

        return $this->page($request, 'Placeholders', 'placeholders', ['items' => $items]);
    }

    public function updatePlaceholder(Request $request, LegalPlaceholder $placeholder): RedirectResponse
    {
        $placeholder->load('version');
        abort_if($placeholder->version->status->isImmutable(), 422, 'Published history cannot be changed. Create a new Draft.');
        $data = $request->validate(['status' => ['required', Rule::in(['open', 'resolved', 'not_applicable'])], 'assigned_owner' => ['nullable', 'string', 'max:255'], 'resolution' => ['nullable', 'string', 'max:5000']]);
        $placeholder->update([...$data, 'resolved_at' => $data['status'] === 'open' ? null : now(), 'resolved_by' => $data['status'] === 'open' ? null : (string) $request->user()->id]);

        return back()->with('success', 'Placeholder state updated.');
    }

    public function settings(Request $request): Response
    {
        return $this->page($request, 'Legal Governance Settings', 'settings', ['settings' => [
            'default_locale' => config('legal-governance.default_locale'), 'checksum_algorithm' => config('legal-governance.checksum_algorithm'),
            'enabled_artifacts' => config('legal-governance.enabled_artifacts'), 'capture_ip' => config('legal-governance.capture_ip'),
            'public_route_prefix' => config('legal-governance.public_route_prefix'), 'placeholder_patterns' => config('legal-governance.placeholder_patterns'),
        ]]);
    }

    private function page(Request $request, string $title, string $kind, array $props = []): Response
    {
        return Inertia::render('LineWatt/LegalGovernanceOperations', [...$props, 'title' => $title, 'kind' => $kind, 'workspace' => [
            'role_label' => LineWattRole::label($request->user()?->role),
            'is_super_admin' => $request->user()?->role === LineWattRole::SUPER_ADMIN,
            'can_review' => (bool) $request->user()?->hasLegalPermission('legal.versions.review'),
            'can_approve' => (bool) $request->user()?->hasLegalPermission('legal.versions.approve'),
            'can_publish' => (bool) $request->user()?->hasLegalPermission('legal.versions.publish'),
            'can_manage_workflows' => (bool) $request->user()?->hasLegalPermission('legal.workflows.edit'),
        ]]);
    }

    private function versionSummary(LegalDocumentVersion $version): array
    {
        return ['id' => $version->public_id, 'document' => $version->document->title, 'category' => $version->document->category, 'version' => $version->version_label, 'status' => $version->status->value, 'checksum' => $version->content_checksum, 'change_summary' => $version->change_summary, 'submitted_at' => $version->updated_at?->toIso8601String(), 'open_blockers' => $version->placeholders->where('status', 'open')->where('release_blocking', true)->count(), 'reviews' => $version->reviews->map(fn ($review) => ['type' => $review->review_type, 'decision' => $review->decision, 'checksum' => $review->reviewed_checksum, 'comments' => $review->comments, 'reviewed_at' => $review->reviewed_at?->toIso8601String()])->all()];
    }

    private function validateRequirement(Request $request): array
    {
        return $request->validate([
            'document_id' => ['required', 'uuid'], 'sequence' => ['required', 'integer', 'min:1'],
            'version_selection_rule' => ['required', Rule::in(['current_published', 'current_effective', 'latest_material_version', 'specific_version'])],
            'specific_version' => ['nullable', 'required_if:version_selection_rule,specific_version', 'string', 'max:80'],
            'acceptance_type' => ['required', Rule::in(['clickwrap_acceptance', 'acknowledgement', 'optional_consent', 'electronic_signature', 'organisation_execution', 'no_acceptance_required'])],
            'is_required' => ['required', 'boolean'], 'blocking_behavior' => ['required', Rule::in(['notice_only', 'next_login_block', 'checkout_block', 'feature_block', 'credential_block', 'organisation_admin_required'])],
            'statement' => ['nullable', 'required_if:is_required,true', 'string', 'max:2000'],
        ]);
    }

    private function textDiff(string $before, string $after): array
    {
        $old = preg_split('/\R/', $before) ?: [];
        $new = preg_split('/\R/', $after) ?: [];
        $lines = [];
        foreach (array_unique([...$old, ...$new]) as $line) {
            if (! in_array($line, $old, true)) {
                $lines[] = ['type' => 'added', 'text' => $line];
            } elseif (! in_array($line, $new, true)) {
                $lines[] = ['type' => 'removed', 'text' => $line];
            }
        }

        return $lines;
    }
}
