<?php

namespace App\LineWatt\Storage;

use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PrivateStorageUsageCalculator
{
    /**
     * @return array{
     *     summary: array<string,mixed>,
     *     breakdown: array<int,array<string,mixed>>,
     *     items: Collection<int,array<string,mixed>>
     * }
     */
    public function forUser(User $user): array
    {
        $quotaBytes = $this->quotaBytes($user);
        $datasheets = $this->datasheets($user)->get();
        $records = $this->records($user)->with('datasheet')->get();

        $datasheetBytes = (int) $datasheets->sum(fn (DeviceDatasheet $datasheet): int => (int) ($datasheet->datasheet_size_bytes ?? 0));
        $recordBytes = (int) $records->sum(fn (CompiledDeviceRecord $record): int => $this->artifactSize($record->compiled_disk, $record->compiled_path));
        $comparisonReportBytes = 0;
        $exportBytes = 0;
        $usedBytes = $datasheetBytes + $recordBytes + $comparisonReportBytes + $exportBytes;

        $items = collect()
            ->merge($datasheets->map(fn (DeviceDatasheet $datasheet): array => $this->datasheetItem($datasheet)))
            ->merge($records->map(fn (CompiledDeviceRecord $record): array => $this->recordItem($record)))
            ->sortByDesc('uploaded_at')
            ->values();

        return [
            'summary' => [
                'used_bytes' => $usedBytes,
                'used_label' => $this->formatBytes($usedBytes),
                'quota_bytes' => $quotaBytes,
                'quota_label' => $this->formatBytes($quotaBytes),
                'used_percent' => $quotaBytes > 0 ? min(100, round(($usedBytes / $quotaBytes) * 100, 1)) : 0,
                'plan_code' => $user->plan_code ?: 'subscriber',
            ],
            'breakdown' => [
                $this->breakdownRow('private_datasheets', 'Private Datasheets', $datasheetBytes),
                $this->breakdownRow('engineering_records', 'Engineering Records', $recordBytes),
                $this->breakdownRow('comparison_reports', 'Comparison Reports', $comparisonReportBytes),
                $this->breakdownRow('exports', 'Exports', $exportBytes),
            ],
            'items' => $items,
        ];
    }

    public function quotaBytes(User $user): int
    {
        $plan = strtolower((string) ($user->plan_code ?: 'subscriber'));
        $quotas = config('linewatt-library.storage_quotas_mb', []);
        $quotaMb = (int) ($quotas[$plan] ?? $quotas['default'] ?? 1024);

        return $quotaMb * 1024 * 1024;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<DeviceDatasheet>
     */
    public function datasheets(User $user)
    {
        $query = DeviceDatasheet::query()->whereRaw('1 = 0');

        if (! Schema::hasTable('device_datasheets')) {
            return $query;
        }

        return DeviceDatasheet::query()
            ->withCount('compiledRecords')
            ->whereIn('source_type', $this->privateSourceTypes())
            ->where(function ($query) use ($user): void {
                $query->where('tenant_id', $user->id)->orWhereNull('tenant_id');
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<CompiledDeviceRecord>
     */
    public function records(User $user)
    {
        $query = CompiledDeviceRecord::query()->whereRaw('1 = 0');

        if (! Schema::hasTable('compiled_device_records')) {
            return $query;
        }

        return CompiledDeviceRecord::query()
            ->whereIn('source_type', $this->privateSourceTypes())
            ->where(function ($query) use ($user): void {
                $query->where('tenant_id', $user->id)->orWhereNull('tenant_id');
            });
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 2).' GB';
        }

        if ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    private function breakdownRow(string $key, string $label, int $bytes): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'bytes' => $bytes,
            'size' => $this->formatBytes($bytes),
        ];
    }

    private function datasheetItem(DeviceDatasheet $datasheet): array
    {
        $dependentCount = (int) ($datasheet->compiled_records_count ?? 0);

        return [
            'key' => 'datasheet:'.($datasheet->uuid ?: $datasheet->id),
            'id' => $datasheet->uuid ?: (string) $datasheet->id,
            'type' => 'datasheet',
            'name' => $datasheet->product_name ?: $datasheet->datasheet_original_filename ?: 'Private datasheet',
            'category' => 'Private Datasheets',
            'size_bytes' => (int) ($datasheet->datasheet_size_bytes ?? 0),
            'size' => $this->formatBytes((int) ($datasheet->datasheet_size_bytes ?? 0)),
            'uploaded_at' => optional($datasheet->created_at)->toDateTimeString(),
            'last_accessed_at' => optional($datasheet->updated_at)->toDateTimeString(),
            'can_be_regenerated' => false,
            'dependent_records_count' => $dependentCount,
            'delete_warning' => $dependentCount > 0
                ? 'Deleting this original PDF can also remove dependent Engineering Records.'
                : null,
        ];
    }

    private function recordItem(CompiledDeviceRecord $record): array
    {
        $bytes = $this->artifactSize($record->compiled_disk, $record->compiled_path);

        return [
            'key' => 'record:'.($record->uuid ?: $record->id),
            'id' => $record->uuid ?: (string) $record->id,
            'type' => 'record',
            'name' => $record->display_name ?: $record->model_name ?: $record->model_series ?: 'Engineering Record',
            'category' => 'Engineering Records',
            'size_bytes' => $bytes,
            'size' => $this->formatBytes($bytes),
            'uploaded_at' => optional($record->created_at)->toDateTimeString(),
            'last_accessed_at' => optional($record->updated_at)->toDateTimeString(),
            'can_be_regenerated' => true,
            'dependent_records_count' => 0,
            'delete_warning' => 'Deleting this Engineering Record does not delete the original PDF.',
        ];
    }

    private function artifactSize(?string $disk, ?string $path): int
    {
        if (! $disk || ! $path) {
            return 0;
        }

        try {
            return Storage::disk($disk)->exists($path) ? (int) Storage::disk($disk)->size($path) : 0;
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * @return list<string>
     */
    private function privateSourceTypes(): array
    {
        return ['tenant_private', 'pvsyst_import'];
    }
}
