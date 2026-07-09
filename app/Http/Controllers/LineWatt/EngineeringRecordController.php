<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LineWatt\Concerns\BuildsEngineeringRecordPayloads;
use App\LineWatt\Access\Entitlement;
use App\LineWatt\Access\EntitlementChecker;
use App\LineWatt\Pdf\DatasheetPdfPolicy;
use App\Models\CompiledDeviceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class EngineeringRecordController extends Controller
{
    use BuildsEngineeringRecordPayloads;

    public function show(Request $request, string $record): Response
    {
        abort_unless(Schema::hasTable('compiled_device_records'), 404);

        $compiledRecord = CompiledDeviceRecord::query()
            ->with('datasheet')
            ->where(function (Builder $query) use ($record): void {
                $query->where('uuid', $record);

                if (ctype_digit($record)) {
                    $query->orWhere('id', (int) $record);
                }
            })
            ->where(function (Builder $query) use ($request): void {
                $query
                    ->where(function (Builder $central): void {
                        $central
                            ->where('source_type', 'central_curated')
                            ->where('status', 'published');
                    });

                if ($request->user()) {
                    $query
                        ->orWhere(function (Builder $tenant) use ($request): void {
                            $tenant
                                ->where('source_type', 'tenant_private')
                                ->where('tenant_id', $request->user()?->id);
                        })
                        ->orWhere(function (Builder $pvsyst) use ($request): void {
                            $pvsyst
                                ->where('source_type', 'pvsyst_import')
                                ->where('tenant_id', $request->user()?->id);
                        })
                        ->orWhere(function (Builder $partner) use ($request): void {
                            $partner
                                ->where('source_type', 'partner_submitted')
                                ->where('partner_id', $request->user()?->id);
                        });
                }
            })
            ->firstOrFail();

        return Inertia::render('LineWatt/EngineeringRecordDetail', [
            'record' => $this->recordSummary($compiledRecord),
            'compiledSummary' => $this->compiledJsonSummary($compiledRecord),
            'compiledRecord' => $this->compiledJsonPresentation($compiledRecord),
            'pdfPolicy' => $compiledRecord->datasheet
                ? app(DatasheetPdfPolicy::class)->viewPayload($request->user(), $compiledRecord->datasheet)
                : null,
            'exportOptions' => $this->exportOptions($request, $compiledRecord),
            'libraryDebug' => (bool) config('linewatt-library.debug'),
        ]);
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function exportOptions(Request $request, CompiledDeviceRecord $record): array
    {
        $entitlements = app(EntitlementChecker::class);
        $pdfPolicy = app(DatasheetPdfPolicy::class);
        $key = (string) ($record->uuid ?: $record->id);
        $canExport = $entitlements->has($request->user(), Entitlement::LIBRARY_EXPORT);
        $canJson = (bool) config('linewatt-library.debug')
            || $entitlements->has($request->user(), Entitlement::CENTRAL_MANAGE);
        $canDownloadPdf = $record->datasheet
            && ($pdfPolicy->canPrivateDownload($request->user(), $record->datasheet)
                || ($record->source_type === 'central_curated' && $record->status === 'published' && $pdfPolicy->canPublicDownload($record->datasheet)));

        $options = [
            $this->exportOption('Original datasheet PDF', 'datasheet', (bool) $canDownloadPdf, 'View datasheet at manufacturer website.', $key),
            $this->exportOption('CSV', 'csv', $canExport, 'Available with subscription.', $key),
            $this->exportOption('Engineering Summary PDF', 'summary-pdf', $canExport, 'Available with subscription.', $key),
            $this->exportOption('JSON', 'json', $canExport && $canJson, $canJson ? 'Available with subscription.' : 'JSON export is restricted.', $key),
        ];

        if ($record->device_type === 'module') {
            array_splice($options, 2, 0, [
                $this->exportOption('PAN', 'pan', $canExport, 'Available with subscription.', $key),
            ]);
        }

        if ($record->device_type === 'inverter') {
            array_splice($options, 2, 0, [
                $this->exportOption('OND', 'ond', $canExport, 'Available with subscription.', $key),
            ]);
        }

        return $options;
    }

    /**
     * @return array<string,mixed>
     */
    private function exportOption(string $label, string $format, bool $enabled, ?string $reason, string $record): array
    {
        return [
            'label' => $label,
            'format' => $format,
            'enabled' => $enabled,
            'reason' => $enabled ? null : $reason,
            'href' => route('records.export', ['record' => $record, 'format' => $format]),
        ];
    }
}
