<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\DeviceScan\Golden\GoldenJsonValidator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('device-scan:validate-golden-json')]
#[Description('Validate module and inverter Golden JSON fixtures against the LineWatt golden contract.')]
final class DeviceScanValidateGoldenJsonCommand extends Command
{
    public function __construct(
        private readonly GoldenJsonValidator $validator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $files = [
            ...$this->jsonFiles(storage_path('app/private/device-scan/golden/modules')),
            ...$this->jsonFiles(storage_path('app/private/device-scan/golden/inverters')),
        ];

        if ($files === []) {
            $this->warn('No Golden JSON fixtures found.');

            return self::FAILURE;
        }

        $failed = 0;
        $rows = [];

        foreach ($files as $file) {
            $decoded = json_decode((string) file_get_contents($file), true);
            $errors = is_array($decoded) ? $this->validator->validate($decoded) : ['invalid_json'];

            if ($errors !== []) {
                $failed++;
            }

            $rows[] = [
                $this->relativePath($file),
                $errors === [] ? 'ok' : 'failed',
                $errors === [] ? '-' : implode(', ', $errors),
            ];
        }

        $this->table(['Fixture', 'Status', 'Errors'], $rows);
        $this->newLine();
        $this->info(sprintf('Validated %d Golden JSON fixture(s), %d failed.', count($files), $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function jsonFiles(string $path): array
    {
        $files = glob($path.DIRECTORY_SEPARATOR.'*.json') ?: [];
        sort($files);

        return array_values($files);
    }

    private function relativePath(string $path): string
    {
        return ltrim(str_replace(base_path().DIRECTORY_SEPARATOR, '', $path), DIRECTORY_SEPARATOR);
    }
}
