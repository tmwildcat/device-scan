<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LineWatt\Concerns\BuildsEngineeringRecordPayloads;
use App\LineWatt\Storage\PrivateStorageUsageCalculator;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class MyLibraryController extends Controller
{
    use BuildsEngineeringRecordPayloads;

    public function __invoke(Request $request, PrivateStorageUsageCalculator $storage): Response
    {
        $userId = $request->user()?->id;
        $storageSummary = $storage->forUser($request->user())['summary'];
        $privateUploadCount = 0;
        $compiledRecordCount = 0;
        $needsReviewCount = 0;
        $reviewRecords = [];
        $recentRecords = [];
        $uploadStatusCounts = [
            'security_checked' => 0,
            'compiling' => 0,
            'compiled' => 0,
            'failed' => 0,
            'rejected' => 0,
        ];

        if (Schema::hasTable('device_datasheets')) {
            $privateUploadCount = DeviceDatasheet::query()
                ->whereIn('source_type', $this->privateSourceTypes())
                ->where(function ($query) use ($userId): void {
                    $query->whereNull('tenant_id')->orWhere('tenant_id', $userId);
                })
                ->count();

            foreach (array_keys($uploadStatusCounts) as $status) {
                $uploadStatusCounts[$status] = DeviceDatasheet::query()
                    ->whereIn('source_type', $this->privateSourceTypes())
                    ->where(function ($query) use ($userId): void {
                        $query->whereNull('tenant_id')->orWhere('tenant_id', $userId);
                    })
                    ->where('status', $status)
                    ->count();
            }
        }

        if (Schema::hasTable('compiled_device_records')) {
            $compiledRecordCount = CompiledDeviceRecord::query()
                ->whereIn('source_type', $this->privateSourceTypes())
                ->where(function ($query) use ($userId): void {
                    $query->whereNull('tenant_id')->orWhere('tenant_id', $userId);
                })
                ->count();

            $needsReviewCount = CompiledDeviceRecord::query()
                ->whereIn('source_type', $this->privateSourceTypes())
                ->where(function ($query) use ($userId): void {
                    $query->whereNull('tenant_id')->orWhere('tenant_id', $userId);
                })
                ->where(function ($query): void {
                    $query
                        ->whereIn('status', ['review_required', 'compiled'])
                        ->orWhereIn('metadata->review_status', ['pending_review', 'flagged']);
                })
                ->count();

            $reviewRecords = CompiledDeviceRecord::query()
                ->with('datasheet')
                ->whereIn('source_type', $this->privateSourceTypes())
                ->where(function ($query) use ($userId): void {
                    $query->whereNull('tenant_id')->orWhere('tenant_id', $userId);
                })
                ->where(function ($query): void {
                    $query
                        ->whereIn('status', ['review_required', 'compiled'])
                        ->orWhereIn('metadata->review_status', ['pending_review', 'flagged']);
                })
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn (CompiledDeviceRecord $record): array => $this->recordSummary($record))
                ->all();

            $recentRecords = CompiledDeviceRecord::query()
                ->with('datasheet')
                ->whereIn('source_type', $this->privateSourceTypes())
                ->where(function ($query) use ($userId): void {
                    $query->whereNull('tenant_id')->orWhere('tenant_id', $userId);
                })
                ->latest()
                ->limit(4)
                ->get()
                ->map(fn (CompiledDeviceRecord $record): array => $this->recordSummary($record))
                ->all();

            $moduleRecordCount = CompiledDeviceRecord::query()
                ->whereIn('source_type', $this->privateSourceTypes())
                ->where(function ($query) use ($userId): void {
                    $query->whereNull('tenant_id')->orWhere('tenant_id', $userId);
                })
                ->where('device_type', 'module')
                ->count();

            $inverterRecordCount = CompiledDeviceRecord::query()
                ->whereIn('source_type', $this->privateSourceTypes())
                ->where(function ($query) use ($userId): void {
                    $query->whereNull('tenant_id')->orWhere('tenant_id', $userId);
                })
                ->where('device_type', 'inverter')
                ->count();
        }

        return Inertia::render('LineWatt/MyLibrary', [
            'summary' => [
                'storage_used_percent' => $storageSummary['used_percent'],
                'storage_used_label' => $storageSummary['used_label'],
                'storage_quota_label' => $storageSummary['quota_label'],
                'private_uploads' => $privateUploadCount,
                'compiled_records' => $compiledRecordCount,
                'needs_review' => $needsReviewCount,
                'recent_exports' => 0,
                'module_records' => $moduleRecordCount ?? 0,
                'inverter_records' => $inverterRecordCount ?? 0,
                'upload_status_counts' => $uploadStatusCounts,
            ],
            'reviewRecords' => $reviewRecords,
            'recentRecords' => $recentRecords,
        ]);
    }

    /**
     * @return list<string>
     */
    private function privateSourceTypes(): array
    {
        return ['tenant_private', 'pvsyst_import'];
    }
}
