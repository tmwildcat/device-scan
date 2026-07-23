<?php

namespace App\LegalGovernance\Services;

use App\LegalGovernance\Actions\AssignLegalObligations;
use App\LegalGovernance\Contracts\LegalIdentityResolverContract;
use App\LegalGovernance\Data\LegalAccessDecision;
use App\LegalGovernance\Models\LegalAcceptance;
use App\LegalGovernance\Models\LegalObligation;
use App\LegalGovernance\Models\LegalWorkflow;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class LegalAccessService
{
    public function __construct(
        private LegalWorkflowService $workflows,
        private AssignLegalObligations $assign,
        private LegalIdentityResolverContract $identities,
        private LegalAuditService $audit,
    ) {}

    public function decisionForCapability(User $user, string $capability, array $context = []): LegalAccessDecision
    {
        $definition = config('legal-governance.capabilities', [])[$capability] ?? null;
        if (! is_array($definition) || blank($definition['workflow'] ?? null)) {
            return $this->invalid($user, $capability, null, 'workflow_not_found');
        }
        if (! blank($definition['audience'] ?? null) && ! $this->audienceEligible($user, (string) $definition['audience'])) {
            return new LegalAccessDecision(true, false, false, true, collect(), collect(), (string) $definition['workflow'], $capability, 'subject_not_eligible', 'No legal action is required for this account.');
        }

        return $this->decisionForWorkflow($user, (string) $definition['workflow'], [...$context, 'capability' => $capability]);
    }

    public function decisionForWorkflow(User $user, string $workflowKey, array $context = []): LegalAccessDecision
    {
        $capability = $context['capability'] ?? null;
        $workflow = LegalWorkflow::query()->with('requirements.document')->where('application_key', config('legal-governance.application_key'))->where('slug', $workflowKey)->first();
        if (! $workflow) {
            return $this->invalid($user, $capability, $workflowKey, 'workflow_not_found');
        }
        if (! $this->audienceEligible($user, $workflow->audience)) {
            return new LegalAccessDecision(true, false, false, true, collect(), collect(), $workflowKey, $capability, 'subject_not_eligible', 'No legal action is required for this account.');
        }
        if ($workflow->status !== 'active') {
            return $this->invalid($user, $capability, $workflowKey, 'workflow_inactive', $workflow);
        }
        if ($this->workflows->validate($workflow) !== []) {
            return $this->invalid($user, $capability, $workflowKey, 'workflow_invalid', $workflow);
        }

        try {
            $identity = $this->identities->resolve($user);
            $resolved = $this->workflows->requirements($workflow);
            $acceptedVersionIds = LegalAcceptance::query()->where('actor_type', $identity['type'])->where('actor_id', $identity['id'])->where('legal_workflow_id', $workflow->id)->where('status', 'accepted')->pluck('legal_document_version_id');
            $missing = $resolved->filter(fn ($item) => ! $acceptedVersionIds->contains($item['version']->id));
            if ($missing->isNotEmpty()) {
                $this->assign->handle($workflow, $identity, (string) ($context['source'] ?? $capability ?? 'access_evaluation'));
            }
            $outstanding = LegalObligation::query()->with('version.document', 'workflow.requirements')->where('actor_type', $identity['type'])->where('actor_id', $identity['id'])->where('legal_workflow_id', $workflow->id)->where('status', 'pending')->get();
            $requiredIds = $resolved->filter(fn ($item) => $item['requirement']->is_required)->pluck('version.id');
            $blocking = $outstanding->filter(fn ($item) => $requiredIds->contains($item->legal_document_version_id) && $item->blocking_behavior !== 'notice_only')->values();
            $optional = $outstanding->reject(fn ($item) => $blocking->contains('id', $item->id))->values();
            $reason = $blocking->contains(fn ($item) => ($item->source_reference ?? '') === 'material_change_publication') ? 'reacceptance_required' : 'acceptance_required';

            return new LegalAccessDecision($blocking->isEmpty(), $outstanding->isNotEmpty(), $blocking->isNotEmpty(), true, $blocking, $optional, $workflowKey, $capability, $blocking->isEmpty() ? 'allowed' : $reason, $blocking->isEmpty() ? 'Legal requirements are satisfied.' : 'You must review and accept the required agreement before continuing.');
        } catch (Throwable) {
            return $this->invalid($user, $capability, $workflowKey, 'legal_configuration_error', $workflow);
        }
    }

    public function outstandingObligations(User $user, ?string $workflowKey = null, array $context = []): Collection
    {
        $identity = $this->identities->resolve($user);

        return LegalObligation::query()->with('version.document', 'workflow.requirements')->where('actor_type', $identity['type'])->where('actor_id', $identity['id'])->where('status', 'pending')->when($workflowKey, fn ($query) => $query->whereHas('workflow', fn ($workflow) => $workflow->where('slug', $workflowKey)))->orderBy('required_at')->get();
    }

    public function canAccess(User $user, string $capability, array $context = []): bool
    {
        return $this->decisionForCapability($user, $capability, $context)->allowed;
    }

    private function audienceEligible(User $user, string $audience): bool
    {
        return match ($audience) {
            'registered_users' => $user->role === 'guest',
            'subscribers' => $user->role === 'subscriber',
            'manufacturers' => $user->manufacturer_company_id !== null,
            'publishers' => $user->role === 'library_publisher',
            'employees' => in_array($user->role, ['super_admin', 'legal_counsel', 'admin', 'librarian'], true),
            'api_clients' => in_array($user->role, ['subscriber', 'partner_admin', 'partner_user'], true),
            default => false,
        };
    }

    private function invalid(User $user, ?string $capability, ?string $workflowKey, string $reason, ?LegalWorkflow $workflow = null): LegalAccessDecision
    {
        if (Cache::add('legal-config-invalid:'.hash('sha256', $user->id.'|'.$capability.'|'.$reason), true, now()->addMinutes(5))) {
            $this->audit->record('legal_access_configuration_invalid', ['actor_type' => User::class, 'actor_id' => (string) $user->id, 'legal_workflow_id' => $workflow?->id, 'summary' => 'Protected capability failed closed because legal configuration is unavailable.', 'metadata' => ['capability' => $capability, 'reason_code' => $reason]]);
        }

        return new LegalAccessDecision(false, false, true, false, collect(), collect(), $workflowKey, $capability, $reason, 'This feature is temporarily unavailable while its legal requirements are configured.');
    }
}
