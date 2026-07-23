<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\LegalGovernance\Actions\RecordLegalAcceptance;
use App\LegalGovernance\Adapters\LineWattLegalIdentityResolver;
use App\LegalGovernance\Services\LegalWorkflowService;
use App\LineWatt\Access\LineWattRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private LegalWorkflowService $legalWorkflows, private RecordLegalAcceptance $recordAcceptance, private LineWattLegalIdentityResolver $identityResolver) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'legal_actions' => ['nullable', 'array'],
            'legal_actions.*' => ['boolean'],
        ])->validate();

        $workflow = $this->legalWorkflows->resolve('registration', 'registered_users');
        $requirements = $workflow ? $this->legalWorkflows->requirements($workflow) : collect();
        foreach ($requirements as $item) {
            if ($item['requirement']->is_required && ! ($input['legal_actions'][$item['version']->public_id] ?? false)) {
                throw ValidationException::withMessages(['legal_actions' => "You must complete the required legal action for {$item['version']->document->title}."]);
            }
        }

        return DB::transaction(function () use ($input, $workflow, $requirements): User {
            $user = User::create(['name' => $input['name'], 'email' => $input['email'], 'password' => $input['password'], 'role' => LineWattRole::SUBSCRIBER, 'plan_code' => 'demo_member', 'subscription_status' => 'active']);
            $identity = $this->identityResolver->resolve($user);
            foreach ($requirements as $item) {
                if (! ($input['legal_actions'][$item['version']->public_id] ?? false)) {
                    continue;
                }
                $statement = $item['requirement']->configuration['statement'] ?? "I complete the stated legal action for {$item['version']->document->title} {$item['version']->version_label}.";
                $this->recordAcceptance->handle($item['version'], $identity, $item['requirement']->acceptance_type, $statement, ['method' => 'registration', 'locale' => app()->getLocale(), 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent(), 'session_reference' => request()->hasSession() ? request()->session()->getId() : null, 'request_reference' => request()->header('X-Request-ID')], $workflow);
            }

            return $user;
        });
    }
}
