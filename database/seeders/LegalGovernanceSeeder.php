<?php

namespace Database\Seeders;

use App\LegalGovernance\Actions\ImportLegalMarkdown;
use App\LegalGovernance\Models\LegalDocument;
use App\LegalGovernance\Models\LegalWorkflow;
use App\LegalGovernance\Models\LegalWorkflowRequirement;
use App\LineWatt\Access\LineWattRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class LegalGovernanceSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }
        $counsel = User::query()->updateOrCreate(['email' => 'legal-counsel@linewatt.test'], ['name' => 'LineWatt Legal Counsel', 'password' => Hash::make(env('DEMO_USER_PASSWORD', 'password')), 'email_verified_at' => now(), 'role' => LineWattRole::LEGAL_COUNSEL, 'plan_code' => null, 'subscription_status' => null]);
        User::query()->updateOrCreate(['email' => 'legal-publisher@linewatt.test'], ['name' => 'LineWatt Legal Publisher', 'password' => Hash::make(env('DEMO_USER_PASSWORD', 'password')), 'email_verified_at' => now(), 'role' => LineWattRole::LEGAL_PUBLISHER, 'plan_code' => null, 'subscription_status' => null]);
        app(ImportLegalMarkdown::class)->import(null, (string) $counsel->id);
        foreach ($this->workflows() as $definition) {
            $workflow = LegalWorkflow::query()->updateOrCreate(['application_key' => config('legal-governance.application_key'), 'slug' => $definition['slug']], ['public_id' => (string) Str::uuid(), 'name' => $definition['name'], 'description' => 'Development workflow configuration; inactive until approved versions exist.', 'trigger_type' => $definition['trigger'], 'audience' => $definition['audience'], 'status' => 'draft', 'priority' => 10, 'blocking_behavior' => $definition['blocking'], 'configuration' => ['statement' => $definition['statement']], 'created_by' => (string) $counsel->id, 'updated_by' => (string) $counsel->id]);
            foreach ($definition['documents'] as $sequence => $requirement) {
                $document = LegalDocument::query()->where('slug', $requirement[0])->first();
                if (! $document) {
                    continue;
                }LegalWorkflowRequirement::query()->updateOrCreate(['legal_workflow_id' => $workflow->id, 'legal_document_id' => $document->id], ['sequence' => $sequence + 1, 'version_selection_rule' => 'current_effective', 'acceptance_type' => $requirement[1], 'is_required' => $requirement[2], 'blocking_behavior' => $definition['blocking'], 'configuration' => ['statement' => $requirement[3]]]);
            }
        }
    }

    private function workflows(): array
    {
        return [['slug' => 'registration', 'name' => 'Registration', 'trigger' => 'registration', 'audience' => 'registered_users', 'blocking' => 'next_login_block', 'statement' => 'Registration legal actions', 'documents' => [['terms-of-use', 'clickwrap_acceptance', true, 'I agree to the'], ['registered-user-agreement', 'clickwrap_acceptance', true, 'I agree to the'], ['privacy-policy', 'acknowledgement', true, 'I acknowledge the']]], ['slug' => 'subscriber-checkout', 'name' => 'Subscriber checkout', 'trigger' => 'subscription_checkout', 'audience' => 'subscribers', 'blocking' => 'checkout_block', 'statement' => 'Subscriber legal actions', 'documents' => [['subscriber-agreement', 'clickwrap_acceptance', true, 'I agree to the'], ['billing-and-refund-policy', 'acknowledgement', true, 'I acknowledge the']]], ['slug' => 'manufacturer-onboarding', 'name' => 'Manufacturer onboarding', 'trigger' => 'manufacturer_invitation', 'audience' => 'manufacturers', 'blocking' => 'organisation_admin_required', 'statement' => 'Manufacturer execution', 'documents' => [['manufacturer-agreement', 'organisation_execution', true, 'I execute the'], ['publisher-content-policy', 'acknowledgement', true, 'I acknowledge the']]], ['slug' => 'publisher-submission', 'name' => 'Publisher submission', 'trigger' => 'publisher_submission', 'audience' => 'publishers', 'blocking' => 'feature_block', 'statement' => 'Publisher submission terms', 'documents' => [['publisher-content-policy', 'acknowledgement', true, 'I acknowledge the']]], ['slug' => 'employee-onboarding', 'name' => 'Employee onboarding', 'trigger' => 'employee_onboarding', 'audience' => 'employees', 'blocking' => 'feature_block', 'statement' => 'Employee acknowledgement', 'documents' => [['acceptable-use-policy', 'acknowledgement', true, 'I acknowledge the']]], ['slug' => 'api-mcp-access', 'name' => 'API and MCP access', 'trigger' => 'api_credential_issuance', 'audience' => 'api_clients', 'blocking' => 'credential_block', 'statement' => 'Integration terms', 'documents' => [['api-and-mcp-terms', 'clickwrap_acceptance', true, 'I agree to the']]]];
    }
}
