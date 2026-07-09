<?php

namespace App\LineWatt\Storage;

use DateTimeInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class LineWattStorage
{
    public function diskName(): string
    {
        return (string) config('linewatt-storage.disk', 'swefs');
    }

    public function disk(): FilesystemAdapter
    {
        return Storage::disk($this->diskName());
    }

    public function basePath(?string ...$segments): string
    {
        return $this->join([
            (string) config('linewatt-storage.base_path', ''),
            ...$segments,
        ]);
    }

    public function put(string $path, mixed $contents, array $options = []): bool
    {
        return $this->disk()->put($this->normalizePath($path), $contents, $options);
    }

    public function get(string $path): string
    {
        return (string) $this->disk()->get($this->normalizePath($path));
    }

    public function exists(string $path): bool
    {
        return $this->disk()->exists($this->normalizePath($path));
    }

    public function delete(string $path): bool
    {
        return $this->disk()->delete($this->normalizePath($path));
    }

    public function url(string $path): ?string
    {
        $path = $this->normalizePath($path);
        $configuredUrl = config('filesystems.disks.'.$this->diskName().'.url');

        if (is_string($configuredUrl) && $configuredUrl !== '') {
            return rtrim($configuredUrl, '/').'/'.$path;
        }

        return $this->disk()->url($path);
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiresAt): ?string
    {
        return $this->disk()->temporaryUrl($this->normalizePath($path), $expiresAt);
    }

    /**
     * @return array<string,mixed>
     */
    public function diagnostics(): array
    {
        return [
            'disk' => $this->diskName(),
            'root' => config('linewatt-storage.root'),
            'namespace' => config('linewatt-storage.namespace'),
            'product' => config('linewatt-storage.product'),
            'environment' => config('linewatt-storage.environment'),
            'base_path' => config('linewatt-storage.base_path'),
            'bucket' => config('filesystems.disks.'.$this->diskName().'.bucket'),
            'endpoint' => config('filesystems.disks.'.$this->diskName().'.endpoint'),
            'region' => config('filesystems.disks.'.$this->diskName().'.region'),
        ];
    }

    private function normalizePath(string $path): string
    {
        return $this->join([$path]);
    }

    /**
     * @param array<int,string|null> $segments
     */
    private function join(array $segments): string
    {
        $segments = array_filter(
            array_map(fn (?string $segment): string => trim((string) $segment, '/'), $segments),
            fn (string $segment): bool => $segment !== ''
        );

        return implode('/', $segments);
    }
}
