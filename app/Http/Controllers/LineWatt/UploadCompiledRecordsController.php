<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LineWatt\Concerns\BuildsEngineeringRecordPayloads;
use App\Models\DeviceDatasheet;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UploadCompiledRecordsController extends Controller
{
    use BuildsEngineeringRecordPayloads;

    public function __invoke(Request $request, DeviceDatasheet $datasheet): Response
    {
        $this->authorizeDatasheet($request, $datasheet);

        $records = $datasheet->compiledRecords()
            ->latest()
            ->get()
            ->map(fn ($record): array => [
                ...$this->recordSummary($record),
                'review_href' => $this->reviewHref($record),
            ])
            ->all();

        return Inertia::render('LineWatt/UploadCompiledRecords', [
            'datasheet' => [
                'id' => $datasheet->id,
                'uuid' => $datasheet->uuid,
                'source_type' => $datasheet->source_type,
                'device_type' => $datasheet->device_type,
                'manufacturer' => $datasheet->manufacturer,
                'product_name' => $datasheet->product_name,
                'status' => $datasheet->status,
                'original_filename' => $datasheet->datasheet_original_filename,
            ],
            'records' => $records,
            'backUrl' => match ($datasheet->source_type) {
                'tenant_private' => route('my-library'),
                'partner_submitted' => route('partner'),
                'central_curated' => ($datasheet->metadata['upload_workspace'] ?? null) === 'publisher'
                    ? route('publisher')
                    : route('central-library'),
                default => route('central-library'),
            },
        ]);
    }

    private function authorizeDatasheet(Request $request, DeviceDatasheet $datasheet): void
    {
        if ($datasheet->source_type === 'tenant_private') {
            abort_unless((int) $datasheet->tenant_id === (int) $request->user()?->id, 403);
        }

        if ($datasheet->source_type === 'partner_submitted') {
            abort_unless((int) $datasheet->partner_id === (int) $request->user()?->id, 403);
        }
    }

    private function reviewHref($record): string
    {
        return match ($record->source_type) {
            'tenant_private' => route('my-library.records.review', ['record' => $record->uuid ?: $record->id]),
            'partner_submitted' => route('partner.submissions.review', ['record' => $record->uuid ?: $record->id]),
            'central_curated' => ($record->datasheet?->metadata['upload_workspace'] ?? $record->metadata['upload_workspace'] ?? null) === 'publisher'
                ? route('publisher.review.show', ['record' => $record->uuid ?: $record->id])
                : route('central-library.review', ['record' => $record->uuid ?: $record->id]),
            default => route('central-library.review', ['record' => $record->uuid ?: $record->id]),
        };
    }
}
