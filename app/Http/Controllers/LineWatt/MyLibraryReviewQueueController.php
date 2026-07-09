<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LineWatt\Concerns\BuildsEngineeringRecordPayloads;
use App\Models\CompiledDeviceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class MyLibraryReviewQueueController extends Controller
{
    use BuildsEngineeringRecordPayloads;

    public function __invoke(Request $request): Response
    {
        $records = [
            'data' => [],
            'current_page' => 1,
            'from' => null,
            'last_page' => 1,
            'per_page' => 20,
            'to' => null,
            'total' => 0,
            'links' => [],
        ];

        if (Schema::hasTable('compiled_device_records')) {
            $records = CompiledDeviceRecord::query()
                ->with('datasheet')
                ->whereIn('source_type', ['tenant_private', 'pvsyst_import'])
                ->where(function (Builder $query) use ($request): void {
                    $query
                        ->whereNull('tenant_id')
                        ->orWhere('tenant_id', $request->user()?->id);
                })
                ->where(function (Builder $query): void {
                    $query
                        ->whereIn('status', ['review_required', 'compiled'])
                        ->orWhereIn('metadata->review_status', ['pending_review', 'flagged']);
                })
                ->latest()
                ->paginate(20)
                ->withQueryString()
                ->through(fn (CompiledDeviceRecord $record): array => $this->recordSummary($record))
                ->toArray();
        }

        return Inertia::render('LineWatt/MyLibraryReviewQueue', [
            'records' => $records,
        ]);
    }
}
