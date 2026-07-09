<?php

declare(strict_types=1);

namespace App\Http\Controllers\DeviceScan\Debug;

use App\DeviceScan\Compilers\Inverters\InverterCompiler;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SplFileInfo;

final class InverterCompilerDebugController extends Controller
{
    public function __invoke(Request $request, InverterCompiler $compiler)
    {
        $corpusRoot = storage_path('app/private/device-scan/corpus/inverters');
        $files = $this->pdfFiles($corpusRoot);
        $selected = $request->query('file');
        $selected = is_string($selected) ? $selected : null;
        $dto = null;
        $error = null;

        if ($selected !== null && ! array_key_exists($selected, $files)) {
            $error = 'Selected file was not found in the inverter corpus.';
            $selected = null;
        }

        if ($selected !== null) {
            $dto = $compiler->compile($files[$selected]);
        }

        return view('device-scan.debug.inverter-compiler', [
            'files' => array_keys($files),
            'selected' => $selected,
            'dto' => $dto,
            'dtoArray' => $dto?->toArray(),
            'error' => $error,
        ]);
    }

    /**
     * @return array<string,string>
     */
    private function pdfFiles(string $root): array
    {
        if (! is_dir($root)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );
        $files = [];

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || mb_strtolower($file->getExtension()) !== 'pdf') {
                continue;
            }

            $path = $file->getPathname();
            $relative = Str::of($path)
                ->after($root.DIRECTORY_SEPARATOR)
                ->replace(DIRECTORY_SEPARATOR, '/')
                ->toString();

            $files[$relative] = $path;
        }

        ksort($files);

        return $files;
    }
}
