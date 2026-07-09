<?php

namespace App\DeviceScan\Storage;

use Illuminate\Support\Str;
use InvalidArgumentException;

class DeviceScanPathBuilder
{
    /**
     * @param array{source_type:string,device_type:string,manufacturer?:?string,product_name?:?string,tenant_uuid?:?string,partner_uuid?:?string,datasheet_uuid?:?string,extension?:?string} $context
     */
    public function buildDatasheetPath(array $context): string
    {
        $extension = $this->extension($context['extension'] ?? 'pdf');

        return $this->join([
            $this->scopeRoot($context, partnerSubmission: true),
            $this->folder('datasheet_folder'),
            $this->deviceType($context['device_type'] ?? null),
            $this->slug($context['manufacturer'] ?? 'unknown-manufacturer'),
            $this->slug($context['product_name'] ?? 'unknown-product'),
            ($context['datasheet_uuid'] ?? (string) Str::uuid()).'.'.$extension,
        ]);
    }

    /**
     * @param array{source_type:string,device_type:string,manufacturer?:?string,product_name?:?string,model_name?:?string,model_series?:?string,display_name?:?string,tenant_uuid?:?string,partner_uuid?:?string,compiled_uuid?:?string} $context
     */
    public function buildCompiledJsonPath(array $context): string
    {
        return $this->jsonPath($context, $this->folder('compiled_folder'), $context['compiled_uuid'] ?? (string) Str::uuid());
    }

    /**
     * @param array{source_type:string,device_type:string,manufacturer?:?string,product_name?:?string,model_name?:?string,model_series?:?string,display_name?:?string,tenant_uuid?:?string,partner_uuid?:?string,review_uuid?:?string} $context
     */
    public function buildReviewJsonPath(array $context): string
    {
        return $this->jsonPath($context, $this->folder('review_folder'), $context['review_uuid'] ?? (string) Str::uuid());
    }

    public function centralRoot(): string
    {
        return $this->expand((string) config('device-scan.central_path'));
    }

    public function tenantRoot(string $tenantUuid): string
    {
        return $this->expand((string) config('device-scan.tenant_path'), ['tenant_uuid' => $tenantUuid]);
    }

    public function partnerRoot(string $partnerUuid): string
    {
        return $this->expand((string) config('device-scan.partner_path'), ['partner_uuid' => $partnerUuid]);
    }

    public function slug(?string $value): string
    {
        $slug = Str::slug(trim((string) $value));

        return $slug !== '' ? $slug : 'unknown';
    }

    /**
     * @param array<string,mixed> $context
     */
    private function jsonPath(array $context, string $folder, string $uuid): string
    {
        return $this->join([
            $this->scopeRoot($context),
            $folder,
            $this->deviceType($context['device_type'] ?? null),
            $this->slug($context['manufacturer'] ?? 'unknown-manufacturer'),
            $this->slug($context['product_name'] ?? 'unknown-product'),
            $this->slug($context['model_name'] ?? $context['model_series'] ?? $context['display_name'] ?? 'unknown-model'),
            $uuid.'.json',
        ]);
    }

    /**
     * @param array<string,mixed> $context
     */
    private function scopeRoot(array $context, bool $partnerSubmission = false): string
    {
        return match ($context['source_type'] ?? null) {
            'central_curated' => $this->centralRoot(),
            'tenant_private' => $this->tenantRoot($this->requiredUuid($context, 'tenant_uuid')),
            'pvsyst_import' => $this->tenantRoot($this->requiredUuid($context, 'tenant_uuid')),
            'partner_submitted' => $partnerSubmission
                ? $this->partnerSubmissionRoot($this->requiredUuid($context, 'partner_uuid'))
                : $this->partnerRoot($this->requiredUuid($context, 'partner_uuid')),
            default => throw new InvalidArgumentException('Unsupported device scan source type.'),
        };
    }

    private function partnerSubmissionRoot(string $partnerUuid): string
    {
        return $this->join([$this->partnerRoot($partnerUuid), 'submissions']);
    }

    /**
     * @param array<string,mixed> $context
     */
    private function requiredUuid(array $context, string $key): string
    {
        $value = $context[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException("Missing required path value [{$key}].");
        }

        return $value;
    }

    private function deviceType(?string $deviceType): string
    {
        if (! in_array($deviceType, config('device-scan.allowed_device_types', []), true)) {
            throw new InvalidArgumentException('Unsupported device scan device type.');
        }

        return $deviceType;
    }

    private function folder(string $key): string
    {
        return trim((string) config('device-scan.'.$key), '/');
    }

    /**
     * @param array<string,string> $replacements
     */
    private function expand(string $path, array $replacements = []): string
    {
        $replacements = [
            'base_path' => (string) config('device-scan.base_path'),
            ...$replacements,
        ];

        foreach ($replacements as $key => $value) {
            $path = str_replace('{'.$key.'}', trim($value, '/'), $path);
        }

        return trim($path, '/');
    }

    /**
     * @param string[] $segments
     */
    private function join(array $segments): string
    {
        return implode('/', array_map(fn (string $segment) => trim($segment, '/'), array_filter($segments, fn ($segment) => $segment !== null && $segment !== '')));
    }

    private function extension(string $extension): string
    {
        $extension = strtolower(trim($extension, '. '));

        return preg_replace('/[^a-z0-9]+/', '', $extension) ?: 'pdf';
    }
}
