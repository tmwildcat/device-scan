<?php

namespace App\Http\Controllers;

use App\LegalGovernance\Actions\RecordLegalAcceptance;
use App\LegalGovernance\Contracts\LegalIdentityResolverContract;
use App\LegalGovernance\Models\LegalAcceptance;
use App\LegalGovernance\Models\LegalObligation;
use App\LegalGovernance\Services\LegalAccessService;
use App\LegalGovernance\Services\LegalAuditService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

final class LegalAcceptanceController extends Controller
{
    public function index(Request $request, LegalAccessService $access, LegalAuditService $audit): Response
    {
        $user = $request->user();
        $capability = $request->session()->get('legal.capability.'.$user->id);
        $decision = $capability ? $access->decisionForCapability($user, $capability) : null;
        $obligations = $access->outstandingObligations($user, $decision?->workflowKey);
        $audit->record('legal_acceptance_flow_entered', ['actor_type' => User::class, 'actor_id' => (string) $user->id, 'summary' => 'User entered the legal acceptance flow.', 'metadata' => ['capability' => $capability]]);

        return Inertia::render('LineWatt/LegalAcceptance', ['capability' => $capability, 'configuration_valid' => $decision?->configurationValid ?? true, 'reason_code' => $decision?->reasonCode, 'obligations' => $obligations->map(fn ($item) => $this->obligationData($item)), 'completed_count' => LegalAcceptance::where('actor_type', User::class)->where('actor_id', (string) $user->id)->where('status', 'accepted')->count()]);
    }

    public function accept(Request $request, LegalObligation $obligation, LegalIdentityResolverContract $identities, RecordLegalAcceptance $record, LegalAccessService $access, LegalAuditService $audit): RedirectResponse
    {
        $request->validate(['affirmed' => ['accepted']]);
        $user = $request->user();
        abort_unless($obligation->actor_type === User::class && $obligation->actor_id === (string) $user->id, 404);

        DB::transaction(function () use ($request, $obligation, $user, $identities, $record, $audit): void {
            $locked = LegalObligation::query()->lockForUpdate()->findOrFail($obligation->id);
            if ($locked->status === 'completed') {
                return;
            }
            abort_unless($locked->status === 'pending', 422);
            $locked->load('version.document', 'workflow.requirements');
            if ($locked->version->status->value !== 'published') {
                throw new LogicException('The governed version is not available for acceptance.');
            }
            $requirement = $locked->workflow->requirements->firstWhere('legal_document_id', $locked->version->legal_document_id);
            abort_unless($requirement, 422);
            $statement = (string) ($requirement->configuration['statement'] ?? '');
            abort_if($statement === '', 422);
            $record->handle($locked->version, $identities->resolve($user), $requirement->acceptance_type, $statement, ['subject_type' => 'user', 'subject_id' => (string) $user->id, 'method' => 'web', 'locale' => $user->preferred_locale ?? 'en', 'ip_address' => $request->ip(), 'user_agent' => $request->userAgent(), 'session_reference' => $request->session()->getId(), 'workflow_requirement_id' => $requirement->id], $locked->workflow, $locked);
            $audit->record('legal_obligation_satisfied', ['actor_type' => User::class, 'actor_id' => (string) $user->id, 'legal_document_version_id' => $locked->version->id, 'legal_workflow_id' => $locked->workflow->id, 'summary' => 'A legal obligation was satisfied.']);
        });

        $capability = $request->session()->get('legal.capability.'.$user->id);
        if ($capability && $access->decisionForCapability($user, $capability)->allowed) {
            $destination = $request->session()->pull('legal.intended.'.$user->id, '/');
            $request->session()->forget('legal.capability.'.$user->id);
            $audit->record('legal_intended_destination_restored', ['actor_type' => User::class, 'actor_id' => (string) $user->id, 'summary' => 'Restored a validated internal destination after legal acceptance.']);

            return redirect()->to($this->safeDestination($destination));
        }

        return redirect()->route('legal.acceptance.index')->with('success', 'Acceptance recorded. Continue with the next required document.');
    }

    public function status(Request $request, LegalAccessService $access): Response
    {
        $user = $request->user();

        return Inertia::render('LineWatt/LegalStatus', ['outstanding' => $access->outstandingObligations($user)->map(fn ($item) => $this->obligationData($item)), 'accepted' => LegalAcceptance::query()->with('version.document', 'workflow')->where('actor_type', User::class)->where('actor_id', (string) $user->id)->where('status', 'accepted')->latest('accepted_at')->get()->map(fn ($item) => ['reference' => $item->public_id, 'document' => $item->version->document->title, 'version' => $item->version->version_label, 'accepted_at' => $item->accepted_at?->toIso8601String(), 'workflow' => $item->workflow?->name])]);
    }

    private function obligationData(LegalObligation $item): array
    {
        return ['id' => $item->public_id, 'document' => $item->version->document->title, 'version' => $item->version->version_label, 'effective_at' => $item->version->effective_at?->toIso8601String(), 'published_at' => $item->version->published_at?->toIso8601String(), 'workflow' => $item->workflow->name, 'audience' => $item->workflow->audience, 'blocking' => $item->blocking_behavior !== 'notice_only', 'material_change' => $item->source_reference === 'material_change_publication', 'statement' => $item->workflow->requirements->firstWhere('legal_document_id', $item->version->legal_document_id)?->configuration['statement'] ?? null, 'document_url' => route('legal.show', $item->version->document->slug)];
    }

    private function safeDestination(mixed $destination): string
    {
        return is_string($destination) && str_starts_with($destination, '/') && ! str_starts_with($destination, '//') ? $destination : '/';
    }
}
