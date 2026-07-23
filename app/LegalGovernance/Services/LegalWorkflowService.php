<?php

namespace App\LegalGovernance\Services;

use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Models\LegalWorkflow;
use App\LegalGovernance\Models\LegalWorkflowRequirement;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use LogicException;

final class LegalWorkflowService
{
    public function validate(LegalWorkflow $workflow): array
    {
        $errors = [];
        $workflow->loadMissing('requirements.document');
        if (! $workflow->audience) {
            $errors[] = 'Audience is required.';
        }if ($workflow->requirements->isEmpty()) {
            $errors[] = 'At least one document requirement is required.';
        }if (! in_array($workflow->trigger_type, config('legal-governance.supported_triggers', []), true)) {
            $errors[] = 'The workflow trigger is not integrated by this application.';
        }foreach ($workflow->requirements as $r) {
            if ($r->acceptance_type === 'consent' && $r->document->document_type === 'notice') {
                $errors[] = "Privacy notice {$r->document->title} cannot be universal consent.";
            }if ($r->acceptance_type === 'optional_consent' && $r->is_required) {
                $errors[] = "Optional consent {$r->document->title} cannot be required.";
            }if (in_array($r->document->visibility, ['internal', 'confidential'], true)) {
                $errors[] = "Internal document {$r->document->title} cannot be presented.";
            }if (! $this->selectVersion($r)) {
                $errors[] = "{$r->document->title} has no selectable version.";
            }if ($r->is_required && blank($r->configuration['statement'] ?? null)) {
                $errors[] = "{$r->document->title} requires an acceptance statement.";
            }
        }

        return $errors;
    }

    public function resolve(string $trigger, string $audience): ?LegalWorkflow
    {
        if (! Schema::hasTable('legal_workflows')) {
            return null;
        }

        return LegalWorkflow::query()->with('requirements.document')->where('application_key', config('legal-governance.application_key'))->where('trigger_type', $trigger)->where('audience', $audience)->where('status', 'active')->where(fn ($q) => $q->whereNull('effective_from')->orWhere('effective_from', '<=', now()))->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>', now()))->orderByDesc('priority')->first();
    }

    public function requirements(LegalWorkflow $workflow): Collection
    {
        return $workflow->requirements->map(function ($r) {
            $version = $this->selectVersion($r);
            if (! $version && $r->is_required) {
                throw new LogicException("Required legal version unavailable: {$r->document->slug}");
            }

            return $version ? ['requirement' => $r, 'version' => $version] : null;
        })->filter()->values();
    }

    private function selectVersion(LegalWorkflowRequirement $r): ?LegalDocumentVersion
    {
        $q = $r->document->versions();

        return match ($r->version_selection_rule) {
            'specific_version' => $q->where('version_label', $r->specific_version)->where('status', 'published')->first(),'current_published' => $q->where('status', 'published')->latest('published_at')->first(),'latest_material_version' => $q->where('status', 'published')->where('is_material_change', true)->latest('published_at')->first(),'current_effective' => $q->where('status', 'published')->where(fn ($x) => $x->whereNull('effective_at')->orWhere('effective_at', '<=', now()))->latest('effective_at')->first(),default => null
        };
    }
}
