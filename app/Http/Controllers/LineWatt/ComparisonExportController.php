<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\Entitlement;
use App\LineWatt\Access\EntitlementChecker;
use App\LineWatt\Access\LineWattRole;
use App\LineWatt\Exports\SimplePdf;
use App\Models\CompiledDeviceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ComparisonExportController extends Controller
{
    public function __construct(private readonly EntitlementChecker $entitlements) {}

    public function __invoke(Request $request, SimplePdf $pdf): SymfonyResponse
    {
        abort_unless($this->entitlements->has($request->user(), Entitlement::LIBRARY_EXPORT), 403, 'Available with subscription.');
        abort_unless(Schema::hasTable('compiled_device_records'), 404);

        $recordKeys = collect(explode(',', (string) $request->query('records', '')))
            ->map(fn (string $record): string => trim($record))
            ->filter()
            ->unique()
            ->take(3)
            ->values();

        abort_unless($recordKeys->count() >= 2, 422, 'Select at least two Engineering Records to compare.');

        $records = $this->loadRecords($request, $recordKeys->all());

        abort_unless($records->count() >= 2, 404, 'Could not load enough visible Engineering Records for comparison.');
        abort_unless($records->pluck('device_type')->filter()->unique()->count() <= 1, 422, 'Compare supports one product type at a time.');

        $filename = 'linewatt-comparison-'.now()->format('Ymd-His').'.pdf';

        return Response::make($pdf->make($this->reportLines($records)), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @param  list<string>  $recordKeys
     */
    private function loadRecords(Request $request, array $recordKeys)
    {
        $numericIds = collect($recordKeys)
            ->filter(fn (string $record): bool => ctype_digit($record))
            ->map(fn (string $record): int => (int) $record)
            ->values()
            ->all();
        $uuids = collect($recordKeys)
            ->filter(fn (string $record): bool => ! ctype_digit($record))
            ->filter(fn (string $record): bool => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $record) === 1)
            ->values()
            ->all();

        return $this->visibleRecordQuery($request)
            ->where(function (Builder $query) use ($numericIds, $uuids): void {
                if ($numericIds !== []) {
                    $query->whereIn('id', $numericIds);
                }

                if ($uuids !== []) {
                    $method = $numericIds === [] ? 'whereIn' : 'orWhereIn';
                    $query->{$method}('uuid', $uuids);
                }
            })
            ->get()
            ->sortBy(fn (CompiledDeviceRecord $record): int => $this->recordOrder($record, $recordKeys))
            ->values();
    }

    /**
     * @return Builder<CompiledDeviceRecord>
     */
    private function visibleRecordQuery(Request $request): Builder
    {
        $user = $request->user();

        return CompiledDeviceRecord::query()
            ->with('datasheet')
            ->where(function (Builder $query) use ($user): void {
                $query->where(function (Builder $central): void {
                    $central
                        ->where('source_type', 'central_curated')
                        ->where('status', 'published');
                });

                if (! $user) {
                    return;
                }

                if ($user->role === LineWattRole::SUBSCRIBER) {
                    $query->orWhere(function (Builder $tenant) use ($user): void {
                        $tenant
                            ->whereIn('source_type', ['tenant_private', 'pvsyst_import'])
                            ->where('tenant_id', $user->id);
                    });
                }

                if (in_array($user->role, LineWattRole::partnerRoles(), true)) {
                    $query->orWhere(function (Builder $partner) use ($user): void {
                        $partner
                            ->where('source_type', 'partner_submitted')
                            ->where('partner_id', $user->id);
                    });
                }
            });
    }

    /**
     * @param  list<string>  $recordKeys
     */
    private function recordOrder(CompiledDeviceRecord $record, array $recordKeys): int
    {
        $idIndex = array_search((string) $record->id, $recordKeys, true);
        $uuidIndex = array_search((string) $record->uuid, $recordKeys, true);

        return $idIndex !== false ? (int) $idIndex : ($uuidIndex !== false ? (int) $uuidIndex : 999);
    }

    /**
     * @param  iterable<CompiledDeviceRecord>  $records
     * @return list<string>
     */
    private function reportLines(iterable $records): array
    {
        $records = collect($records)->values();
        $names = $records
            ->map(fn (CompiledDeviceRecord $record): string => $record->display_name ?: $record->model_name ?: $record->model_series ?: 'Engineering Record')
            ->all();

        $lines = [
            'LineWatt Library Engineering Comparison',
            'Generated: '.now()->toDateTimeString(),
            'Device type: '.($records->first()?->device_type ?: 'Engineering Records'),
            'Records: '.implode(' | ', $names),
            '',
            'Overview',
        ];

        foreach ($records as $record) {
            $lines[] = '- '.($record->display_name ?: 'Engineering Record')
                .' | '.($record->manufacturer ?: 'Unknown manufacturer')
                .' | '.($record->model_series ?: 'No model series')
                .' | '.($record->power_class_w ? $record->power_class_w.' W' : ($record->power_class_kw ? $record->power_class_kw.' kW' : 'No power class'))
                .' | '.($record->source_type ?: 'unknown scope');
        }

        $lines[] = '';
        $lines[] = 'Compared Engineering Values';

        foreach ($this->comparisonFields((string) $records->first()?->device_type) as $label => $paths) {
            $values = $records->map(function (CompiledDeviceRecord $record) use ($paths): string {
                $payload = $this->recordPayload($record);
                $value = null;

                foreach ($paths as $path) {
                    $candidate = Arr::get($payload, $path);
                    if ($candidate !== null && $candidate !== '') {
                        $value = $candidate;
                        break;
                    }
                }

                return $this->displayValue($value);
            })->all();

            $lines[] = $label.': '.implode(' | ', $values);
        }

        $lines[] = '';
        $lines[] = 'Qualitative / Expert Analysis';
        $lines[] = 'This v1 report highlights side-by-side extracted engineering values only. Detailed expert interpretation, project-specific design checks and procurement recommendations remain outside this export and should be completed by a qualified engineer.';
        $lines[] = '';
        $lines[] = 'Generated by LineWatt Library';
        $lines[] = 'Date/time: '.now()->toDateTimeString();
        $lines[] = 'Record names: '.implode(' | ', $names);
        $lines[] = 'Disclaimer: Preliminary engineering information. Verify before design use.';

        return $lines;
    }

    /**
     * @return array<string,list<string>>
     */
    private function comparisonFields(string $deviceType): array
    {
        if ($deviceType === 'module') {
            return [
                'Pmax' => ['electrical_stc.models.0.pmax', 'electrical_stc.models.0.maximum_power'],
                'Voc' => ['electrical_stc.models.0.voc', 'electrical_stc.models.0.open_circuit_voltage'],
                'Vmp' => ['electrical_stc.models.0.vmp', 'electrical_stc.models.0.maximum_power_voltage'],
                'Isc' => ['electrical_stc.models.0.isc', 'electrical_stc.models.0.short_circuit_current'],
                'Imp' => ['electrical_stc.models.0.imp', 'electrical_stc.models.0.maximum_power_current'],
                'Efficiency' => ['electrical_stc.models.0.efficiency', 'electrical_stc.models.0.module_efficiency'],
                'Dimensions' => ['mechanical.dimensions'],
                'Weight' => ['mechanical.weight_kg'],
                'Maximum system voltage' => ['operating_conditions.maximum_system_voltage'],
                'Product warranty' => ['warranty.product_warranty_years'],
            ];
        }

        return [
            'Recommended max PV power' => ['dc_input.recommended_max_pv_power'],
            'Max DC voltage' => ['dc_input.max_dc_voltage'],
            'MPPT voltage range' => ['dc_input.mppt_voltage_range'],
            'MPPT count' => ['dc_input.mppt_count'],
            'Rated AC power' => ['ac_output.rated_ac_power'],
            'Rated AC voltage' => ['ac_output.rated_ac_voltage'],
            'Rated frequency' => ['ac_output.rated_frequency', 'ac_output.frequency'],
            'Max output current' => ['ac_output.max_output_current'],
            'DC SPD' => ['protection.has_dc_spd'],
            'AC SPD' => ['protection.has_ac_spd'],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function recordPayload(CompiledDeviceRecord $record): array
    {
        $review = $record->metadata['review_artifact'] ?? null;

        if (is_array($review)) {
            $reviewPayload = $this->readJsonArtifact($review['disk'] ?? null, $review['path'] ?? null);
            if ($reviewPayload !== null) {
                return $reviewPayload;
            }
        }

        return $this->readJsonArtifact($record->compiled_disk, $record->compiled_path) ?? [];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function readJsonArtifact(?string $diskName, ?string $path): ?array
    {
        if (! $diskName || ! $path) {
            return null;
        }

        $disk = Storage::disk($diskName);

        if (! $disk->exists($path)) {
            return null;
        }

        $decoded = json_decode($disk->get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    private function displayValue(mixed $value): string
    {
        if (is_array($value) && array_key_exists('value', $value)) {
            $unit = $value['unit'] ?? '';
            $display = $this->displayValue($value['normalized_value'] ?? $value['value']);

            return trim($display.' '.$unit);
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES) ?: '—';
        }

        if ($value === null || $value === '') {
            return '—';
        }

        return (string) $value;
    }
}
