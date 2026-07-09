<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\Entitlement;
use App\LineWatt\Access\EntitlementChecker;
use App\LineWatt\Exports\SimplePdf;
use App\LineWatt\Pdf\DatasheetPdfPolicy;
use App\Models\CompiledDeviceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class EngineeringRecordExportController extends Controller
{
    public function __construct(
        private readonly EntitlementChecker $entitlements,
        private readonly DatasheetPdfPolicy $pdfPolicy,
    ) {}

    public function __invoke(Request $request, string $record, string $format, SimplePdf $pdf): SymfonyResponse
    {
        $compiledRecord = $this->record($request, $record);

        if ($format === 'datasheet') {
            return $this->datasheet($request, $compiledRecord);
        }

        abort_unless($this->entitlements->has($request->user(), Entitlement::LIBRARY_EXPORT), 403, 'Available with subscription.');
        abort_unless($this->formatAllowedForDevice($compiledRecord, $format), 404);

        $payload = $this->compiledPayload($compiledRecord);
        $filename = $this->filename($compiledRecord, $format);

        return match ($format) {
            'csv' => Response::make($this->csv($payload), 200, $this->headers($filename, 'text/csv')),
            'pan' => Response::make($this->keyValue($payload, 'PAN'), 200, $this->headers($filename, 'text/plain')),
            'ond' => Response::make($this->keyValue($payload, 'OND'), 200, $this->headers($filename, 'text/plain')),
            'summary-pdf' => Response::make($pdf->make($this->summaryLines($compiledRecord, $payload)), 200, $this->headers($filename, 'application/pdf')),
            'json' => $this->json($request, $compiledRecord, $payload, $filename),
            default => abort(404),
        };
    }

    private function datasheet(Request $request, CompiledDeviceRecord $record): SymfonyResponse
    {
        abort_unless($request->user(), 403);
        abort_unless($record->datasheet?->datasheet_disk && $record->datasheet?->datasheet_path, 404);
        abort_unless(
            $this->pdfPolicy->canPrivateDownload($request->user(), $record->datasheet)
                || ($record->source_type === 'central_curated' && $record->status === 'published' && $this->pdfPolicy->canPublicDownload($record->datasheet)),
            403,
            'Original datasheet PDF download is not available for this source.'
        );

        $disk = Storage::disk($record->datasheet->datasheet_disk);
        abort_unless($disk->exists($record->datasheet->datasheet_path), 404);

        return Response::make($disk->get($record->datasheet->datasheet_path), 200, $this->headers(
            $record->datasheet->datasheet_original_filename ?: 'datasheet.pdf',
            'application/pdf'
        ));
    }

    private function json(Request $request, CompiledDeviceRecord $record, array $payload, string $filename): SymfonyResponse
    {
        abort_unless(
            (bool) config('linewatt-library.debug') || $this->entitlements->has($request->user(), Entitlement::CENTRAL_MANAGE),
            403,
            'JSON export is restricted.'
        );

        return Response::make(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200, $this->headers($filename, 'application/json'));
    }

    private function record(Request $request, string $record): CompiledDeviceRecord
    {
        return CompiledDeviceRecord::query()
            ->with('datasheet')
            ->where(function (Builder $query) use ($record): void {
                $query->where('uuid', $record);

                if (ctype_digit($record)) {
                    $query->orWhere('id', (int) $record);
                }
            })
            ->where(function (Builder $query) use ($request): void {
                $query->where(function (Builder $central): void {
                    $central->where('source_type', 'central_curated')->where('status', 'published');
                });

                if ($request->user()) {
                    $query->orWhere(fn (Builder $tenant) => $tenant->where('source_type', 'tenant_private')->where('tenant_id', $request->user()?->id));
                    $query->orWhere(fn (Builder $pvsyst) => $pvsyst->where('source_type', 'pvsyst_import')->where('tenant_id', $request->user()?->id));
                    $query->orWhere(fn (Builder $partner) => $partner->where('source_type', 'partner_submitted')->where('partner_id', $request->user()?->id));
                }
            })
            ->firstOrFail();
    }

    /**
     * @return array<string,mixed>
     */
    private function compiledPayload(CompiledDeviceRecord $record): array
    {
        abort_unless($record->compiled_disk && $record->compiled_path, 404);

        $disk = Storage::disk($record->compiled_disk);
        abort_unless($disk->exists($record->compiled_path), 404);

        $decoded = json_decode($disk->get($record->compiled_path), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function csv(array $payload): string
    {
        $rows = [['field', 'value']];

        foreach ($this->flatten($payload) as $field => $value) {
            $rows[] = [$field, $value];
        }

        return collect($rows)
            ->map(fn (array $row): string => implode(',', array_map(fn (mixed $cell): string => '"'.str_replace('"', '""', (string) $cell).'"', $row)))
            ->implode("\n")."\n";
    }

    private function keyValue(array $payload, string $format): string
    {
        $lines = ["# LineWatt Library {$format} export", '# Generated: '.now()->toIso8601String()];

        foreach ($this->flatten($payload) as $field => $value) {
            $lines[] = $field.'='.$value;
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @return list<string>
     */
    private function summaryLines(CompiledDeviceRecord $record, array $payload): array
    {
        $lines = [
            'LineWatt Library Engineering Summary',
            'Generated: '.now()->toDateTimeString(),
            'Record: '.($record->display_name ?: $record->model_name ?: 'Engineering Record'),
            'Manufacturer: '.($record->manufacturer ?: 'Pending'),
            'Device type: '.($record->device_type ?: 'Pending'),
            '',
        ];

        foreach (array_slice($this->flatten($payload), 0, 55) as $field => $value) {
            $lines[] = $field.': '.$value;
        }

        $lines[] = '';
        $lines[] = 'Generated by LineWatt Library';
        $lines[] = 'Preliminary engineering information. Verify before design use.';

        return $lines;
    }

    /**
     * @return array<string,string>
     */
    private function flatten(array $payload, string $prefix = ''): array
    {
        $flat = [];

        foreach ($payload as $key => $value) {
            if (in_array($key, ['raw_json', 'metadata'], true)) {
                continue;
            }

            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

            if (is_array($value) && Arr::isAssoc($value)) {
                $flat += $this->flatten($value, $path);
                continue;
            }

            if (is_array($value)) {
                $flat[$path] = json_encode($value, JSON_UNESCAPED_SLASHES) ?: '';
                continue;
            }

            $flat[$path] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        return $flat;
    }

    private function filename(CompiledDeviceRecord $record, string $format): string
    {
        $base = str($record->display_name ?: $record->model_name ?: 'engineering-record')->slug()->toString();
        $extension = match ($format) {
            'summary-pdf' => 'pdf',
            default => $format,
        };

        return $base.'.'.$extension;
    }

    /**
     * @return array<string,string>
     */
    private function headers(string $filename, string $mime): array
    {
        return [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }

    private function formatAllowedForDevice(CompiledDeviceRecord $record, string $format): bool
    {
        return match ($format) {
            'pan' => $record->device_type === 'module',
            'ond' => $record->device_type === 'inverter',
            default => true,
        };
    }
}
