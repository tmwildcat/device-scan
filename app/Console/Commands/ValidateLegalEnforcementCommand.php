<?php

namespace App\Console\Commands;

use App\LegalGovernance\Models\LegalWorkflow;
use App\LegalGovernance\Services\LegalWorkflowService;
use App\LegalGovernance\Services\PublicLegalDocumentService;
use Illuminate\Console\Command;

final class ValidateLegalEnforcementCommand extends Command
{
    protected $signature = 'legal:validate-enforcement {--allow-staged : Report Draft workflow mappings as warnings instead of errors}';

    protected $description = 'Validate protected capability, workflow, requirement and public-footer configuration';

    public function handle(LegalWorkflowService $workflows, PublicLegalDocumentService $publicDocuments): int
    {
        $errors = [];
        $warnings = [];
        $capabilities = config('legal-governance.capabilities', []);
        if (count($capabilities) !== count(array_unique(array_keys($capabilities)))) {
            $errors[] = 'Duplicate protected capability keys are configured.';
        }
        foreach ($capabilities as $capability => $definition) {
            $workflow = LegalWorkflow::query()->with('requirements.document')->where('application_key', config('legal-governance.application_key'))->where('slug', $definition['workflow'] ?? '')->first();
            if (! $workflow) {
                $errors[] = "{$capability}: mapped workflow does not exist.";

                continue;
            }
            if ($workflow->status !== 'active') {
                if ($this->option('allow-staged')) {
                    $warnings[] = "{$capability}: workflow {$workflow->slug} is {$workflow->status}.";
                } else {
                    $errors[] = "{$capability}: workflow {$workflow->slug} is {$workflow->status}.";
                }

                continue;
            }
            foreach ($workflows->validate($workflow) as $error) {
                $errors[] = "{$capability}: {$error}";
            }
            $duplicates = $workflow->requirements->groupBy('sequence')->filter(fn ($items) => $items->count() > 1)->keys();
            if ($duplicates->isNotEmpty()) {
                $errors[] = "{$capability}: duplicate workflow sequence positions ".$duplicates->join(', ').'.';
            }
        }
        foreach (config('legal-governance.public_footer_documents', []) as $entry) {
            $row = $this->manifestRow((string) $entry['slug']);
            if (! $row) {
                $errors[] = "Footer document {$entry['slug']} is absent from the manifest.";
            } elseif (($row['Visibility'] ?? null) !== 'public') {
                $errors[] = "Footer document {$entry['slug']} is not public.";
            } elseif (! $publicDocuments->publicDocument((string) $entry['slug'])) {
                $message = "Footer document {$entry['slug']} has no currently Published and effective public version.";
                if (($entry['required'] ?? false) && ! $this->option('allow-staged')) {
                    $errors[] = $message;
                } else {
                    $warnings[] = $message;
                }
            }
        }
        $this->table(['Result', 'Count'], [['Capabilities', count($capabilities)], ['Errors', count($errors)], ['Warnings', count($warnings)]]);
        foreach ($errors as $error) {
            $this->error($error);
        }
        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        return $errors === [] ? self::SUCCESS : self::FAILURE;
    }

    private function manifestRow(string $slug): ?array
    {
        foreach (file(config('legal-governance.source_manifest'), FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (str_starts_with(trim($line), '| linewatt-library |')) {
                $cells = array_map('trim', explode('|', trim(trim($line), '|')));
                if (($cells[1] ?? null) === $slug) {
                    return ['Slug' => $cells[1], 'Visibility' => $cells[6] ?? null];
                }
            }
        }

        return null;
    }
}
