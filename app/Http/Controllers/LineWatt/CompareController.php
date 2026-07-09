<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LineWatt\Concerns\BuildsEngineeringRecordPayloads;
use App\LineWatt\Access\Entitlement;
use App\LineWatt\Access\EntitlementChecker;
use App\LineWatt\Access\LineWattRole;
use App\Models\CompiledDeviceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CompareController extends Controller
{
    use BuildsEngineeringRecordPayloads;

    public function __invoke(Request $request): Response
    {
        $recordKeys = collect(explode(',', (string) $request->query('records', '')))
            ->map(fn (string $record): string => trim($record))
            ->filter()
            ->unique()
            ->take(3)
            ->values();

        $records = collect();
        $messages = [];

        if ($recordKeys->count() < 2) {
            $messages[] = 'Select at least two Engineering Records to compare.';
        } elseif (! Schema::hasTable('compiled_device_records')) {
            $messages[] = 'Engineering Record storage is not available.';
        } else {
            $numericIds = $recordKeys
                ->filter(fn (string $record): bool => ctype_digit($record))
                ->map(fn (string $record): int => (int) $record)
                ->values()
                ->all();
            $uuids = $recordKeys
                ->filter(fn (string $record): bool => ! ctype_digit($record))
                ->filter(fn (string $record): bool => preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $record) === 1)
                ->values()
                ->all();

            $records = $this->visibleRecordQuery($request)
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
                ->sortBy(fn (CompiledDeviceRecord $record): int => $this->recordOrder($record, $recordKeys->all()))
                ->values();

            if ($records->count() < 2) {
                $messages[] = 'Could not load enough visible Engineering Records for comparison.';
            }

            if ($records->pluck('device_type')->filter()->unique()->count() > 1) {
                $messages[] = 'Compare supports one product type at a time.';
            }
        }

        $deviceType = $records->pluck('device_type')->filter()->unique()->count() === 1
            ? (string) $records->first()?->device_type
            : null;

        $comparisonRecords = $records
            ->map(fn (CompiledDeviceRecord $record): array => $this->comparisonRecord($record))
            ->values()
            ->all();

        return Inertia::render('LineWatt/Compare', [
            'records' => $comparisonRecords,
            'sections' => $deviceType ? $this->comparisonSections($deviceType, $comparisonRecords) : [],
            'deviceType' => $deviceType,
            'message' => $messages[0] ?? null,
            'messages' => $messages,
            'exportHref' => route('compare.export', ['records' => $recordKeys->implode(',')]),
            'canExportComparison' => app(EntitlementChecker::class)->has($request->user(), Entitlement::LIBRARY_EXPORT),
            'exportDisabledReason' => 'Available with subscription.',
            'libraryDebug' => (bool) config('linewatt-library.debug'),
        ]);
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
     * @param  string[]  $recordKeys
     */
    private function recordOrder(CompiledDeviceRecord $record, array $recordKeys): int
    {
        $idIndex = array_search((string) $record->id, $recordKeys, true);
        $uuidIndex = array_search((string) $record->uuid, $recordKeys, true);

        if ($idIndex !== false) {
            return (int) $idIndex;
        }

        if ($uuidIndex !== false) {
            return (int) $uuidIndex;
        }

        return 999;
    }

    /**
     * @return array<string,mixed>
     */
    private function comparisonRecord(CompiledDeviceRecord $record): array
    {
        $compiled = $this->readJsonArtifact($record->compiled_disk, $record->compiled_path) ?? [];
        $review = $this->readReviewArtifact($record);
        $corrections = $this->reviewCorrections($review);
        $summary = $this->recordSummary($record);

        return [
            ...$summary,
            'comparison_key' => (string) ($record->uuid ?: $record->id),
            'json_source' => $review ? 'reviewed' : 'compiled',
            'compiled_json' => $compiled,
            'review_corrections' => $corrections,
            'raw_json' => config('linewatt-library.debug') ? [
                'compiled' => $compiled,
                'review' => $review,
            ] : null,
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function readReviewArtifact(CompiledDeviceRecord $record): ?array
    {
        $artifact = $record->metadata['review_artifact'] ?? null;

        if (! is_array($artifact)) {
            return null;
        }

        return $this->readJsonArtifact($artifact['disk'] ?? null, $artifact['path'] ?? null);
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

    /**
     * @param  array<string,mixed>|null  $review
     * @return array<string,mixed>
     */
    private function reviewCorrections(?array $review): array
    {
        $corrections = [];

        foreach (($review['sections'] ?? []) as $section) {
            foreach (($section['rows'] ?? []) as $row) {
                $path = (string) ($row['path'] ?? '');

                if ($path === '') {
                    continue;
                }

                $corrections[$path] = [
                    'value' => $row['value'] ?? null,
                    'unit' => $row['unit'] ?? null,
                    'source_page' => $row['page'] ?? null,
                    'source_section' => $row['section'] ?? null,
                    'source_text' => $row['sourceText'] ?? null,
                ];
            }
        }

        return $corrections;
    }

    /**
     * @param  array<int,array<string,mixed>>  $records
     * @return array<int,array<string,mixed>>
     */
    private function comparisonSections(string $deviceType, array $records): array
    {
        $definitions = $deviceType === 'module'
            ? $this->moduleFieldDefinitions()
            : $this->inverterFieldDefinitions();

        return collect($definitions)
            ->map(function (array $section) use ($records): array {
                $rows = collect($section['fields'])
                    ->map(fn (array $field): array => $this->comparisonRow($field, $records))
                    ->filter(fn (array $row): bool => $row['has_any_value'] || ($section['always'] ?? false))
                    ->values()
                    ->all();

                return [
                    'key' => $section['key'],
                    'title' => $section['title'],
                    'rows' => $rows,
                ];
            })
            ->filter(fn (array $section): bool => count($section['rows']) > 0)
            ->values()
            ->all();
    }

    /**
     * @param  array<string,mixed>  $field
     * @param  array<int,array<string,mixed>>  $records
     * @return array<string,mixed>
     */
    private function comparisonRow(array $field, array $records): array
    {
        $values = collect($records)
            ->map(fn (array $record): array => [
                'record_key' => $record['comparison_key'],
                ...$this->fieldValue($record, $field),
            ])
            ->values()
            ->all();

        $presentValues = collect($values)->filter(fn (array $value): bool => ! $value['missing'])->values();
        $numericValues = $presentValues->filter(fn (array $value): bool => $value['numeric'] !== null)->values();
        $normalizedValues = $presentValues
            ->map(fn (array $value): string => mb_strtolower(trim((string) $value['display'])))
            ->unique()
            ->values();

        $min = $numericValues->min('numeric');
        $max = $numericValues->max('numeric');
        $hasDifference = $presentValues->count() > 1 && (
            ($numericValues->count() === $presentValues->count() && $min !== $max)
            || ($numericValues->count() !== $presentValues->count() && $normalizedValues->count() > 1)
        );

        foreach ($values as $index => $value) {
            if ($value['numeric'] === null || ! $hasDifference || $numericValues->count() !== $presentValues->count()) {
                $values[$index]['highlight'] = null;
                continue;
            }

            $values[$index]['highlight'] = match ((float) $value['numeric']) {
                (float) $max => 'high',
                (float) $min => 'low',
                default => null,
            };
        }

        $diffPercent = null;
        if ($hasDifference && $numericValues->count() === $presentValues->count() && (float) $min !== 0.0) {
            $diffPercent = round((((float) $max - (float) $min) / abs((float) $min)) * 100, 2);
        }

        return [
            'key' => $field['key'],
            'label' => $field['label'],
            'kind' => $field['kind'] ?? 'text',
            'values' => $values,
            'has_any_value' => $presentValues->count() > 0,
            'has_difference' => $hasDifference,
            'diff_percent' => $diffPercent,
        ];
    }

    /**
     * @param  array<string,mixed>  $record
     * @param  array<string,mixed>  $field
     * @return array<string,mixed>
     */
    private function fieldValue(array $record, array $field): array
    {
        foreach ($field['paths'] as $path) {
            $reviewed = $this->reviewedValue($record['review_corrections'] ?? [], $path);
            if ($reviewed !== null) {
                return $this->normalizeValue($reviewed, $field);
            }

            $compiled = Arr::get($record['compiled_json'] ?? [], $path);
            if ($this->hasValue($compiled)) {
                return $this->normalizeValue($compiled, $field);
            }
        }

        foreach (($field['summary_paths'] ?? []) as $path) {
            $summary = Arr::get($record, $path);
            if ($this->hasValue($summary)) {
                return $this->normalizeValue($summary, $field);
            }
        }

        return [
            'display' => '—',
            'unit' => null,
            'numeric' => null,
            'missing' => true,
            'source_page' => null,
            'source_section' => null,
            'source_text' => null,
            'highlight' => null,
        ];
    }

    /**
     * @param  array<string,mixed>  $corrections
     * @return array<string,mixed>|null
     */
    private function reviewedValue(array $corrections, string $path): ?array
    {
        $candidates = [
            $path,
            'identity.'.$path,
            'electrical.'.$path,
            'general.'.$path,
        ];

        foreach ($candidates as $candidate) {
            if (array_key_exists($candidate, $corrections) && $this->hasValue($corrections[$candidate]['value'] ?? null)) {
                return $corrections[$candidate];
            }
        }

        return null;
    }

    /**
     * @param  array<string,mixed>  $field
     * @return array<string,mixed>
     */
    private function normalizeValue(mixed $value, array $field): array
    {
        $sourcePage = null;
        $sourceSection = null;
        $sourceText = null;
        $unit = $field['unit'] ?? null;

        if (is_array($value) && ($this->isSourceValue($value) || array_key_exists('value', $value))) {
            $unit = $value['unit'] ?? $unit;
            $sourcePage = $value['source_page'] ?? null;
            $sourceSection = $value['source_section'] ?? null;
            $sourceText = $value['source_text'] ?? null;
            $value = $value['normalized_value'] ?? $value['value'] ?? null;
        }

        if (is_bool($value)) {
            $display = $value ? 'Yes' : 'No';
            $numeric = null;
        } elseif (is_array($value)) {
            $display = implode(', ', array_filter(array_map(fn (mixed $item): string => $this->displayScalar($item), $value)));
            $numeric = null;
        } else {
            $display = $this->displayScalar($value);
            $numeric = $this->numericValue($value);
        }

        if ($display === '') {
            $display = '—';
        }

        if ($unit && $display !== '—' && ! str_contains(mb_strtolower($display), mb_strtolower((string) $unit))) {
            $display .= ' '.$unit;
        }

        return [
            'display' => $display,
            'unit' => $unit,
            'numeric' => $numeric,
            'missing' => $display === '—',
            'source_page' => $sourcePage,
            'source_section' => $sourceSection,
            'source_text' => $sourceText,
            'highlight' => null,
        ];
    }

    private function isSourceValue(array $value): bool
    {
        return array_key_exists('source_text', $value)
            || array_key_exists('source_page', $value)
            || array_key_exists('confidence', $value)
            || array_key_exists('normalized_value', $value);
    }

    private function displayScalar(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES) ?: '';
        }

        return trim((string) $value);
    }

    private function numericValue(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            return null;
        }

        $text = (string) $value;
        if (preg_match('/-?\d+(?:\.\d+)?/', $text, $match) !== 1) {
            return null;
        }

        return (float) $match[0];
    }

    private function hasValue(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_array($value)) {
            return $value !== [] && collect($value)->contains(fn (mixed $nested): bool => $this->hasValue($nested));
        }

        return true;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function moduleFieldDefinitions(): array
    {
        return [
            ['key' => 'overview', 'title' => 'Overview', 'always' => true, 'fields' => [
                $this->field('manufacturer', 'Manufacturer', ['manufacturer'], ['manufacturer']),
                $this->field('display_name', 'Display name', ['display_name'], ['display_name']),
                $this->field('model_series', 'Model series', ['model_series'], ['model_series']),
                $this->field('model_name', 'Model name', ['model_name'], ['model_name']),
                $this->field('power_class_w', 'Power class', ['power_class_w'], ['power_class_w'], 'W', 'number'),
                $this->field('technology', 'Technology', ['technology'], ['technology']),
                $this->field('source', 'Source scope', [], ['source_label']),
                $this->field('validation', 'Validation grade', [], ['validation_grade']),
            ]],
            ['key' => 'stc', 'title' => 'STC Electrical', 'fields' => [
                $this->field('pmax', 'Pmax', ['electrical_stc.pmax', 'electrical_stc.maximum_power', 'electrical_stc.models.0.pmax', 'electrical_stc.models.0.maximum_power'], [], 'W', 'number'),
                $this->field('voc', 'Voc', ['electrical_stc.voc', 'electrical_stc.open_circuit_voltage', 'electrical_stc.models.0.voc', 'electrical_stc.models.0.open_circuit_voltage'], [], 'V', 'number'),
                $this->field('vmp', 'Vmp', ['electrical_stc.vmp', 'electrical_stc.maximum_power_voltage', 'electrical_stc.models.0.vmp', 'electrical_stc.models.0.maximum_power_voltage'], [], 'V', 'number'),
                $this->field('isc', 'Isc', ['electrical_stc.isc', 'electrical_stc.short_circuit_current', 'electrical_stc.models.0.isc', 'electrical_stc.models.0.short_circuit_current'], [], 'A', 'number'),
                $this->field('imp', 'Imp', ['electrical_stc.imp', 'electrical_stc.maximum_power_current', 'electrical_stc.models.0.imp', 'electrical_stc.models.0.maximum_power_current'], [], 'A', 'number'),
                $this->field('efficiency', 'Efficiency', ['electrical_stc.efficiency', 'electrical_stc.module_efficiency', 'electrical_stc.models.0.efficiency', 'electrical_stc.models.0.module_efficiency'], [], '%', 'number'),
            ]],
            ['key' => 'mechanical', 'title' => 'Mechanical', 'fields' => [
                $this->field('dimensions', 'Dimensions', ['mechanical.dimensions']),
                $this->field('length_mm', 'Length', ['mechanical.length_mm'], [], 'mm', 'number'),
                $this->field('width_mm', 'Width', ['mechanical.width_mm'], [], 'mm', 'number'),
                $this->field('thickness_mm', 'Thickness', ['mechanical.thickness_mm'], [], 'mm', 'number'),
                $this->field('weight_kg', 'Weight', ['mechanical.weight_kg'], [], 'kg', 'number'),
                $this->field('cell_type', 'Cell type', ['mechanical.cell_type']),
                $this->field('cell_count', 'Cell count', ['mechanical.cell_count'], [], null, 'number'),
                $this->field('glass', 'Glass', ['mechanical.glass']),
                $this->field('frame', 'Frame', ['mechanical.frame']),
            ]],
            ['key' => 'operating', 'title' => 'Operating', 'fields' => [
                $this->field('maximum_system_voltage', 'Maximum system voltage', ['operating_conditions.maximum_system_voltage'], [], 'V', 'number'),
                $this->field('operating_temperature', 'Operating temperature', ['operating_conditions.operating_temperature']),
                $this->field('maximum_series_fuse_rating', 'Max series fuse rating', ['operating_conditions.maximum_series_fuse_rating'], [], 'A', 'number'),
                $this->field('static_load_front', 'Static load front', ['operating_conditions.static_load_front']),
                $this->field('static_load_back', 'Static load back', ['operating_conditions.static_load_back']),
            ]],
            ['key' => 'temperature', 'title' => 'Temperature', 'fields' => [
                $this->field('noct', 'NOCT / NMOT', ['temperature_characteristics.nominal_operating_cell_temperature', 'operating_conditions.nominal_operating_cell_temperature'], [], 'C', 'number'),
                $this->field('temp_pmax', 'Temp coeff Pmax', ['temperature_characteristics.temperature_coefficient_pmax'], [], '%/C', 'number'),
                $this->field('temp_voc', 'Temp coeff Voc', ['temperature_characteristics.temperature_coefficient_voc'], [], '%/C', 'number'),
                $this->field('temp_isc', 'Temp coeff Isc', ['temperature_characteristics.temperature_coefficient_isc'], [], '%/C', 'number'),
            ]],
            ['key' => 'warranty', 'title' => 'Warranty', 'fields' => [
                $this->field('product_warranty_years', 'Product warranty', ['warranty.product_warranty_years'], [], 'years', 'number'),
                $this->field('linear_power_warranty_years', 'Performance warranty', ['warranty.linear_power_warranty_years'], [], 'years', 'number'),
                $this->field('first_year_degradation_percent', 'First-year degradation', ['warranty.first_year_degradation_percent'], [], '%', 'number'),
                $this->field('annual_degradation_percent', 'Annual degradation', ['warranty.annual_degradation_percent'], [], '%', 'number'),
                $this->field('end_of_warranty_output_percent', 'End-of-warranty output', ['warranty.end_of_warranty_output_percent'], [], '%', 'number'),
            ]],
            ['key' => 'certifications', 'title' => 'Certifications', 'fields' => [
                $this->field('certifications', 'Certifications', ['certifications']),
            ]],
            ['key' => 'validation', 'title' => 'Validation', 'fields' => [
                $this->field('validation_status', 'Validation status', [], ['validation_status']),
                $this->field('validation_grade', 'Validation grade', [], ['validation_grade']),
                $this->field('validation_score', 'Validation score', [], ['validation_score'], null, 'number'),
                $this->field('validation_issues', 'Validation issues', ['validation.issues']),
            ]],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function inverterFieldDefinitions(): array
    {
        return [
            ['key' => 'overview', 'title' => 'Overview', 'always' => true, 'fields' => [
                $this->field('manufacturer', 'Manufacturer', ['manufacturer'], ['manufacturer']),
                $this->field('display_name', 'Display name', ['display_name'], ['display_name']),
                $this->field('model_series', 'Model series', ['model_series'], ['model_series']),
                $this->field('model_name', 'Model name', ['model_name'], ['model_name']),
                $this->field('power_class_kw', 'Power class', ['power_class_kw'], ['power_class_kw'], 'kW', 'number'),
                $this->field('device_type', 'Device type', ['device_type'], ['inverter_device_type', 'device_type']),
                $this->field('source', 'Source scope', [], ['source_label']),
                $this->field('validation', 'Validation grade', [], ['validation_grade']),
            ]],
            ['key' => 'dc_input', 'title' => 'DC Input', 'fields' => [
                $this->field('recommended_max_pv_power', 'Recommended max PV power', ['dc_input.recommended_max_pv_power'], [], 'kW', 'number'),
                $this->field('max_dc_voltage', 'Max DC voltage', ['dc_input.max_dc_voltage'], [], 'V', 'number'),
                $this->field('startup_voltage', 'Startup voltage', ['dc_input.startup_voltage'], [], 'V', 'number'),
                $this->field('rated_dc_voltage', 'Rated DC voltage', ['dc_input.rated_dc_voltage'], [], 'V', 'number'),
                $this->field('mppt_voltage_range', 'MPPT voltage range', ['dc_input.mppt_voltage_range']),
                $this->field('full_power_mppt_range', 'Full power MPPT range', ['dc_input.full_power_mppt_range']),
                $this->field('mppt_count', 'MPPT count', ['dc_input.mppt_count'], [], null, 'number'),
                $this->field('strings_per_mppt', 'Strings per MPPT', ['dc_input.strings_per_mppt'], [], null, 'number'),
                $this->field('max_input_current', 'Max input current', ['dc_input.max_input_current'], [], 'A', 'number'),
                $this->field('max_short_circuit_current', 'Max short circuit current', ['dc_input.max_short_circuit_current'], [], 'A', 'number'),
            ]],
            ['key' => 'ac_output', 'title' => 'AC Output', 'fields' => [
                $this->field('rated_ac_power', 'Rated AC power', ['ac_output.rated_ac_power'], [], 'kW', 'number'),
                $this->field('max_ac_power', 'Max AC power', ['ac_output.max_ac_power'], [], 'kW', 'number'),
                $this->field('rated_apparent_power', 'Rated apparent power', ['ac_output.rated_apparent_power'], [], 'kVA', 'number'),
                $this->field('max_apparent_power', 'Max apparent power', ['ac_output.max_apparent_power'], [], 'kVA', 'number'),
                $this->field('rated_ac_voltage', 'Rated AC voltage', ['ac_output.rated_ac_voltage'], [], 'V', 'number'),
                $this->field('rated_frequency', 'Frequency', ['ac_output.rated_frequency', 'ac_output.frequency']),
                $this->field('rated_output_current', 'Rated output current', ['ac_output.rated_output_current'], [], 'A', 'number'),
                $this->field('max_output_current', 'Max output current', ['ac_output.max_output_current'], [], 'A', 'number'),
                $this->field('power_factor', 'Power factor', ['ac_output.power_factor']),
                $this->field('thd', 'THD', ['ac_output.thd'], [], '%', 'number'),
                $this->field('phase_type', 'Phase type', ['ac_output.phase_type']),
            ]],
            ['key' => 'rated_power_conditions', 'title' => 'Rated Power Conditions', 'fields' => [
                $this->field('rated_power_conditions', 'Rated power conditions', ['rated_power_conditions']),
            ]],
            ['key' => 'protection', 'title' => 'Protection', 'fields' => [
                $this->field('has_dc_switch', 'DC switch', ['protection.has_dc_switch']),
                $this->field('has_dc_spd', 'DC SPD', ['protection.has_dc_spd']),
                $this->field('has_ac_spd', 'AC SPD', ['protection.has_ac_spd']),
                $this->field('has_afci', 'AFCI', ['protection.has_afci']),
                $this->field('has_rcmu', 'RCMU', ['protection.has_rcmu']),
                $this->field('has_anti_islanding_protection', 'Anti-islanding', ['protection.has_anti_islanding_protection']),
                $this->field('has_grid_monitoring', 'Grid monitoring', ['protection.has_grid_monitoring']),
            ]],
            ['key' => 'central_specific', 'title' => 'Central Specific', 'fields' => [
                $this->field('max_dc_inputs', 'Max DC inputs', ['central_specific.max_dc_inputs'], [], null, 'number'),
                $this->field('dc_cabinet_inputs', 'DC cabinet inputs', ['central_specific.dc_cabinet_inputs']),
                $this->field('dc_combiner_required', 'DC combiner required', ['central_specific.dc_combiner_required']),
                $this->field('mv_station_interface', 'MV station interface', ['central_specific.mv_station_interface']),
                $this->field('transformer_interface', 'Transformer interface', ['central_specific.transformer_interface']),
                $this->field('grid_voltage_mv', 'Grid voltage MV', ['central_specific.grid_voltage_mv']),
                $this->field('ac_breaker', 'AC breaker', ['central_specific.ac_breaker']),
                $this->field('cooling_system', 'Cooling system', ['central_specific.cooling_system']),
                $this->field('containerized', 'Containerized', ['central_specific.containerized']),
                $this->field('inverter_blocks', 'Inverter blocks', ['central_specific.inverter_blocks']),
            ]],
            ['key' => 'validation', 'title' => 'Validation', 'fields' => [
                $this->field('validation_status', 'Validation status', [], ['validation_status']),
                $this->field('validation_grade', 'Validation grade', [], ['validation_grade']),
                $this->field('validation_score', 'Validation score', [], ['validation_score'], null, 'number'),
                $this->field('validation_issues', 'Validation issues', ['validation.issues']),
            ]],
        ];
    }

    /**
     * @param  string[]  $paths
     * @param  string[]  $summaryPaths
     * @return array<string,mixed>
     */
    private function field(string $key, string $label, array $paths, array $summaryPaths = [], ?string $unit = null, string $kind = 'text'): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'paths' => $paths,
            'summary_paths' => $summaryPaths,
            'unit' => $unit,
            'kind' => $kind,
        ];
    }
}
