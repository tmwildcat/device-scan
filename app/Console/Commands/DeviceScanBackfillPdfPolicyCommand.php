<?php

namespace App\Console\Commands;

use App\Models\DeviceDatasheet;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'device-scan:backfill-pdf-policy')]
class DeviceScanBackfillPdfPolicyCommand extends Command
{
    protected $description = 'Backfill datasheet PDF distribution policy defaults.';

    public function handle(): int
    {
        $updated = 0;

        DeviceDatasheet::query()
            ->orderBy('id')
            ->each(function (DeviceDatasheet $datasheet) use (&$updated): void {
                $sourceUrl = $datasheet->source_url ?: ($datasheet->metadata['source_url'] ?? null);
                $mode = match ($datasheet->source_type) {
                    'tenant_private' => 'user_private',
                    'partner_submitted' => 'partner_supplied',
                    default => $sourceUrl ? 'external_link_only' : 'internal_only',
                };

                $datasheet->forceFill([
                    'pdf_access_mode' => $datasheet->pdf_access_mode ?: $mode,
                    'source_url' => $sourceUrl,
                    'source_domain' => $datasheet->source_domain ?: $this->domain($sourceUrl),
                    'permission_status' => $datasheet->permission_status ?: 'unknown',
                    'can_public_download' => $mode === 'partner_supplied',
                    'can_public_preview' => $mode === 'partner_supplied',
                    'can_internal_preview' => true,
                    'can_private_download' => true,
                ])->save();

                $updated++;
            });

        $this->info("Backfilled PDF policy for {$updated} datasheets.");

        return self::SUCCESS;
    }

    private function domain(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? strtolower($host) : null;
    }
}
