<?php

namespace App\DeviceScan\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DeviceScanArtifactStorage
{
    public function __construct(
        private readonly DeviceScanPathBuilder $paths,
    ) {}

    /**
     * @param string|File|UploadedFile|resource $file
     * @param array<string,mixed> $context
     * @return array{disk:string,path:string,sha256:string,size_bytes:int|null,mime_type:?string,original_filename:?string}
     */
    public function storeDatasheet(mixed $file, array $context, ?string $disk = null): array
    {
        $disk ??= $this->defaultDisk();
        $path = $context['path'] ?? $this->paths->buildDatasheetPath([
            ...$context,
            'extension' => $context['extension'] ?? $this->extensionForFile($file),
        ]);
        $contents = $this->contents($file);

        $this->disk($disk)->put($path, $contents);

        return [
            'disk' => $disk,
            'path' => $path,
            'sha256' => hash('sha256', $contents),
            'size_bytes' => strlen($contents),
            'mime_type' => $this->mimeTypeForFile($file),
            'original_filename' => $this->originalNameForFile($file),
        ];
    }

    /**
     * @param array<string,mixed>|string $json
     * @param array<string,mixed> $context
     * @return array{disk:string,path:string,sha256:string,size_bytes:int}
     */
    public function storeCompiledJson(array|string $json, array $context, ?string $disk = null): array
    {
        $disk ??= $this->defaultDisk();
        $path = $context['path'] ?? $this->paths->buildCompiledJsonPath($context);
        $contents = is_array($json) ? $this->encodeJson($json) : $json;

        $this->disk($disk)->put($path, $contents);

        return [
            'disk' => $disk,
            'path' => $path,
            'sha256' => hash('sha256', $contents),
            'size_bytes' => strlen($contents),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function readCompiledJson(string $path, ?string $disk = null): array
    {
        $contents = $this->disk($disk ?? $this->defaultDisk())->get($path);
        $decoded = json_decode((string) $contents, true);

        if (! is_array($decoded)) {
            throw new RuntimeException("Compiled JSON at [{$path}] could not be decoded.");
        }

        return $decoded;
    }

    /**
     * @param array<string,mixed>|string $json
     * @param array<string,mixed> $context
     * @return array{disk:string,path:string,sha256:string,size_bytes:int}
     */
    public function storeReviewJson(array|string $json, array $context, ?string $disk = null): array
    {
        $disk ??= $this->defaultDisk();
        $path = $context['path'] ?? $this->paths->buildReviewJsonPath($context);
        $contents = is_array($json) ? $this->encodeJson($json) : $json;

        $this->disk($disk)->put($path, $contents);

        return [
            'disk' => $disk,
            'path' => $path,
            'sha256' => hash('sha256', $contents),
            'size_bytes' => strlen($contents),
        ];
    }

    public function moveDatasheet(string $fromPath, string $toPath, ?string $fromDisk = null, ?string $toDisk = null): void
    {
        $this->move($fromPath, $toPath, $fromDisk, $toDisk);
    }

    public function moveCompiledJson(string $fromPath, string $toPath, ?string $fromDisk = null, ?string $toDisk = null): void
    {
        $this->move($fromPath, $toPath, $fromDisk, $toDisk);
    }

    public function deleteTenantAsset(string $path, string $tenantUuid, ?string $disk = null): bool
    {
        $tenantRoot = $this->paths->tenantRoot($tenantUuid);

        if (! str_starts_with(trim($path, '/'), $tenantRoot.'/')) {
            throw new RuntimeException('Refusing to delete asset outside tenant scope.');
        }

        return $this->delete($path, $disk);
    }

    public function calculateSha256(string $path, ?string $disk = null): string
    {
        return hash('sha256', (string) $this->disk($disk ?? $this->defaultDisk())->get($path));
    }

    public function exists(string $path, ?string $disk = null): bool
    {
        return $this->disk($disk ?? $this->defaultDisk())->exists($path);
    }

    public function copy(string $fromPath, string $toPath, ?string $fromDisk = null, ?string $toDisk = null): void
    {
        $fromDisk ??= $this->defaultDisk();
        $toDisk ??= $fromDisk;
        $contents = $this->disk($fromDisk)->get($fromPath);

        $this->disk($toDisk)->put($toPath, $contents);
    }

    public function move(string $fromPath, string $toPath, ?string $fromDisk = null, ?string $toDisk = null): void
    {
        $fromDisk ??= $this->defaultDisk();
        $toDisk ??= $fromDisk;

        $this->copy($fromPath, $toPath, $fromDisk, $toDisk);
        $this->disk($fromDisk)->delete($fromPath);
    }

    public function delete(string $path, ?string $disk = null): bool
    {
        return $this->disk($disk ?? $this->defaultDisk())->delete($path);
    }

    public function defaultDisk(): string
    {
        return (string) config('device-scan.storage_disk');
    }

    private function disk(string $disk): Filesystem
    {
        return Storage::disk($disk);
    }

    private function encodeJson(array $json): string
    {
        return json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
    }

    /**
     * @param string|File|UploadedFile|resource $file
     */
    private function contents(mixed $file): string
    {
        if ($file instanceof UploadedFile || $file instanceof File) {
            return (string) file_get_contents($file->getPathname());
        }

        if (is_resource($file)) {
            $contents = stream_get_contents($file);

            return $contents === false ? '' : $contents;
        }

        if (is_string($file) && is_file($file)) {
            return (string) file_get_contents($file);
        }

        if (is_string($file)) {
            return $file;
        }

        throw new RuntimeException('Unsupported artifact input.');
    }

    private function extensionForFile(mixed $file): string
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalExtension() ?: 'pdf';
        }

        if ($file instanceof File || (is_string($file) && is_file($file))) {
            return pathinfo($file instanceof File ? $file->getPathname() : $file, PATHINFO_EXTENSION) ?: 'pdf';
        }

        return 'pdf';
    }

    private function mimeTypeForFile(mixed $file): ?string
    {
        if ($file instanceof UploadedFile || $file instanceof File) {
            return $file->getMimeType();
        }

        return null;
    }

    private function originalNameForFile(mixed $file): ?string
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalName();
        }

        if ($file instanceof File || (is_string($file) && is_file($file))) {
            return basename($file instanceof File ? $file->getPathname() : $file);
        }

        return null;
    }
}
