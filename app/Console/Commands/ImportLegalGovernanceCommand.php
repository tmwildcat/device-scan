<?php

namespace App\Console\Commands;

use App\LegalGovernance\Actions\ImportLegalMarkdown;
use Illuminate\Console\Command;

final class ImportLegalGovernanceCommand extends Command
{
    protected $signature = 'legal:import {--manifest=} {--actor=console}';

    protected $description = 'Deliberately import governed legal Markdown as database Drafts';

    public function handle(ImportLegalMarkdown $import): int
    {
        $result = $import->import($this->option('manifest') ?: null, $this->option('actor'));
        $this->table(['Created', 'Unchanged', 'Conflicts'], [[$result['created'], $result['unchanged'], $result['conflicts']]]);

        return $result['conflicts'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
