<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\DeviceScan\Compilers\Inverters\InverterCompiler;
use App\DeviceScan\Compilers\Modules\ModuleCompiler;
use App\DeviceScan\Golden\GoldenJsonBuilder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

#[Signature('device-scan:generate-golden-json {--device= : module or inverter} {--limit= : Optional PDF limit}')]
#[Description('Generate filesystem Golden JSON fixtures from the current module or inverter corpus.')]
final class DeviceScanGenerateGoldenJsonCommand extends Command
{
    public function __construct(
        private readonly ModuleCompiler $moduleCompiler,
        private readonly InverterCompiler $inverterCompiler,
        private readonly GoldenJsonBuilder $builder,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        ini_set('memory_limit', '-1');

        $device = (string) $this->option('device');

        if (! in_array($device, ['module', 'inverter'], true)) {
            $this->error('Use --device=module or --device=inverter.');

            return self::FAILURE;
        }

        $target = storage_path('app/private/device-scan/golden/'.($device === 'module' ? 'modules' : 'inverters'));
        $this->ensureDirectory($target);
        $this->clearJsonFiles($target);

        $created = 0;
        $failed = 0;
        $limit = $this->option('limit') !== null ? max(0, (int) $this->option('limit')) : null;
        $pdfs = $device === 'module' ? $this->modulePdfs() : $this->inverterPdfs();

        if ($limit !== null && $limit > 0) {
            $pdfs = array_slice($pdfs, 0, $limit);
        }

        foreach ($pdfs as $pdfPath) {
            $this->line('Compiling '.$this->relativePath($pdfPath));

            try {
                $records = $device === 'module'
                    ? $this->builder->moduleRecords($this->moduleCompiler->compile($pdfPath), $pdfPath)
                    : [$this->builder->inverterRecord($this->inverterCompiler->compile($pdfPath), $pdfPath)];

                foreach ($records as $index => $record) {
                    $filename = $this->uniqueFilename($target, $record, $index);
                    file_put_contents(
                        $target.DIRECTORY_SEPARATOR.$filename,
                        json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
                    );
                    $created++;
                }

                $this->info('  wrote '.count($records).' golden JSON file(s)');
            } catch (Throwable $exception) {
                $failed++;
                $this->error('  failed: '.$exception->getMessage());
            }
        }

        $this->newLine();
        $this->table(['Device', 'PDFs checked', 'Golden JSON files', 'Failed'], [[$device, count($pdfs), $created, $failed]]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function modulePdfs(): array
    {
        return $this->canonicalPdfs(storage_path('app/private/device-scan/corpus/modules'));
    }

    /**
     * @return list<string>
     */
    private function inverterPdfs(): array
    {
        return $this->canonicalPdfs(storage_path('app/private/device-scan/corpus/inverters'));
    }

    /**
     * @return list<string>
     */
    private function canonicalPdfs(string $root): array
    {
        $paths = glob($root.'/**/*.pdf') ?: [];
        $paths = [...$paths, ...(glob($root.'/*.pdf') ?: [])];
        $byBasename = [];

        foreach ($paths as $path) {
            $basename = basename($path);
            $existing = $byBasename[$basename] ?? null;

            if ($existing === null || dirname($existing) === $root) {
                $byBasename[$basename] = $path;
            }
        }

        sort($byBasename);

        return array_values($byBasename);
    }

    private function uniqueFilename(string $target, array $record, int $index): string
    {
        $filename = $this->builder->filename($record);

        if (! is_file($target.DIRECTORY_SEPARATOR.$filename)) {
            return $filename;
        }

        $base = Str::beforeLast($filename, '.json');

        return $base.'-'.($index + 1).'.json';
    }

    private function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }

    private function clearJsonFiles(string $target): void
    {
        foreach (glob($target.DIRECTORY_SEPARATOR.'*.json') ?: [] as $file) {
            unlink($file);
        }
    }

    private function relativePath(string $path): string
    {
        return ltrim(str_replace(base_path().DIRECTORY_SEPARATOR, '', $path), DIRECTORY_SEPARATOR);
    }
}
