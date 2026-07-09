<?php

namespace App\LineWatt\Uploads;

use App\Models\DeviceDatasheet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadSecurityService
{
    public function __construct(
        private readonly MalwareScanner $scanner,
    ) {}

    /**
     * @param array{source_type:string,device_type:string,tenant_id?:?int,partner_id?:?int} $scope
     */
    public function inspect(string $path, ?UploadedFile $file, array $scope): UploadSecurityResult
    {
        $errors = [];
        $warnings = [];
        $metadata = [
            'checked_at' => now()->toIso8601String(),
            'source_type' => $scope['source_type'] ?? null,
            'device_type' => $scope['device_type'] ?? null,
        ];

        if (! is_file($path)) {
            return UploadSecurityResult::fail(['upload_file_missing'], metadata: $metadata);
        }

        $sizeBytes = filesize($path) ?: 0;
        $sha256 = hash_file('sha256', $path) ?: null;
        $mimeType = $this->detectMimeType($path, $file);
        $extension = Str::lower($file?->getClientOriginalExtension() ?: pathinfo($path, PATHINFO_EXTENSION));
        $maxBytes = max(1, (int) config('linewatt-library.upload.max_pdf_size_mb', 25)) * 1024 * 1024;

        if (! in_array($extension, config('linewatt-library.upload.allowed_extensions', ['pdf']), true)) {
            $errors[] = 'unsupported_file_extension';
        }

        if (! in_array($mimeType, config('linewatt-library.upload.allowed_mime_types', ['application/pdf']), true)) {
            $errors[] = 'unsupported_file_type';
        }

        if ($sizeBytes <= 0) {
            $errors[] = 'empty_file';
        }

        if ($sizeBytes > $maxBytes) {
            $errors[] = 'file_too_large';
        }

        if (! $this->hasPdfSignature($path)) {
            $errors[] = 'invalid_pdf_signature';
        }

        if ($this->appearsEncrypted($path)) {
            $errors[] = 'encrypted_pdf_not_supported';
        }

        if ($sha256 !== null && $this->duplicateExists($sha256, $scope)) {
            $errors[] = 'duplicate_upload';
        }

        $scanResult = $this->scan($path);
        $metadata['malware_scan'] = [
            'status' => $scanResult->status,
            'message' => $scanResult->message,
            'metadata' => $scanResult->metadata,
        ];

        if ($scanResult->status === 'skipped') {
            $warnings[] = 'malware_scan_skipped';
        }

        if (! $scanResult->passed) {
            $errors[] = 'malware_scan_failed';
        }

        if ($errors !== []) {
            return UploadSecurityResult::fail(
                array_values(array_unique($errors)),
                array_values(array_unique($warnings)),
                $sha256,
                $sizeBytes,
                $mimeType,
                $metadata,
            );
        }

        return UploadSecurityResult::pass(
            (string) $sha256,
            $sizeBytes,
            $mimeType,
            array_values(array_unique($warnings)),
            $metadata,
        );
    }

    private function detectMimeType(string $path, ?UploadedFile $file): ?string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detected = $finfo ? finfo_file($finfo, $path) : null;

        if ($finfo) {
            finfo_close($finfo);
        }

        if (is_string($detected) && $detected !== '') {
            return $detected;
        }

        if ($file instanceof UploadedFile && is_readable($file->getPathname())) {
            $mimeType = $file->getMimeType();

            if (is_string($mimeType) && $mimeType !== '') {
                return $mimeType;
            }
        }

        return null;
    }

    private function hasPdfSignature(string $path): bool
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return false;
        }

        $signature = fread($handle, 5);
        fclose($handle);

        return $signature === '%PDF-';
    }

    private function appearsEncrypted(string $path): bool
    {
        $contents = file_get_contents($path, false, null, 0, min(filesize($path) ?: 0, 1024 * 1024));

        if (! is_string($contents)) {
            return false;
        }

        return str_contains($contents, '/Encrypt')
            || str_contains($contents, '/Filter /Standard')
            || str_contains($contents, '/EncryptMetadata');
    }

    /**
     * @param array{source_type:string,device_type:string,tenant_id?:?int,partner_id?:?int} $scope
     */
    private function duplicateExists(string $sha256, array $scope): bool
    {
        return DeviceDatasheet::query()
            ->where('datasheet_sha256', $sha256)
            ->where('source_type', $scope['source_type'])
            ->where('device_type', $scope['device_type'])
            ->when(array_key_exists('tenant_id', $scope), fn ($query) => $query->where('tenant_id', $scope['tenant_id']))
            ->when(array_key_exists('partner_id', $scope), fn ($query) => $query->where('partner_id', $scope['partner_id']))
            ->exists();
    }

    private function scan(string $path): MalwareScanResult
    {
        if (! (bool) config('linewatt-library.upload.malware_scan.enabled', false)) {
            return MalwareScanResult::skipped('Malware scanning is disabled by configuration.', [
                'driver' => 'disabled',
            ]);
        }

        $result = $this->scanner->scan($path);

        if ($result->status === 'skipped' && (bool) config('linewatt-library.upload.malware_scan.fail_closed', false)) {
            return MalwareScanResult::failed('Malware scanner unavailable and fail-closed is enabled.', $result->metadata);
        }

        return $result;
    }
}
