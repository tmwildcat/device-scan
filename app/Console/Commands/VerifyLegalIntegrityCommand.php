<?php

namespace App\Console\Commands;

use App\LegalGovernance\Services\LegalIntegrityVerifier;
use Illuminate\Console\Command;

final class VerifyLegalIntegrityCommand extends Command
{
    protected $signature = 'legal:verify-integrity {--actor=scheduler}';

    protected $description = 'Verify legal artifacts, manifests and acceptance evidence without modifying them';

    public function handle(LegalIntegrityVerifier $verifier): int
    {
        $result = $verifier->verify((string) $this->option('actor'));
        $this->info("Checked {$result['checked']} retained legal records in {$result['duration_ms']} ms.");
        if ($result['discrepancies'] !== []) {
            $this->table(['Type', 'Reference'], $result['discrepancies']);

            return self::FAILURE;
        }
        $this->info('No integrity discrepancies detected.');

        return self::SUCCESS;
    }
}
