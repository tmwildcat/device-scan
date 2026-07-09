<?php

namespace App\Http\Controllers\LineWatt\Concerns;

use App\Models\CompiledDeviceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

trait BuildsEngineeringRecordPayloads
{
    /**
     * @return Builder<CompiledDeviceRecord>
     */
    private function centralCuratedRecordQuery(): Builder
    {
        return CompiledDeviceRecord::query()
            ->with('datasheet')
            ->where('source_type', 'central_curated')
            ->where('status', 'published');
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function recentCentralRecords(int $limit = 8): array
    {
        if (! Schema::hasTable('compiled_device_records')) {
            return [];
        }

        return $this->centralCuratedRecordQuery()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (CompiledDeviceRecord $record): array => $this->recordSummary($record))
            ->all();
    }

    /**
     * @return array{total:int,modules:int,inverters:int}
     */
    private function centralLibraryStats(): array
    {
        if (! Schema::hasTable('compiled_device_records')) {
            return ['total' => 0, 'modules' => 0, 'inverters' => 0];
        }

        $counts = $this->centralCuratedRecordQuery()
            ->select('device_type', DB::raw('count(*) as aggregate'))
            ->groupBy('device_type')
            ->pluck('aggregate', 'device_type');

        return [
            'total' => (int) $counts->sum(),
            'modules' => (int) ($counts['module'] ?? 0),
            'inverters' => (int) ($counts['inverter'] ?? 0),
        ];
    }

    /**
     * @return array<int,array{manufacturer:string,count:int}>
     */
    private function centralManufacturerCounts(int $limit = 8): array
    {
        if (! Schema::hasTable('compiled_device_records')) {
            return [];
        }

        return $this->centralCuratedRecordQuery()
            ->select('manufacturer', DB::raw('count(*) as aggregate'))
            ->whereNotNull('manufacturer')
            ->where('manufacturer', '<>', '')
            ->groupBy('manufacturer')
            ->orderByDesc('aggregate')
            ->orderBy('manufacturer')
            ->limit($limit)
            ->get()
            ->map(fn (CompiledDeviceRecord $record): array => [
                'manufacturer' => (string) $record->manufacturer,
                'count' => (int) $record->aggregate,
            ])
            ->all();
    }

    /**
     * @return string[]
     */
    private function centralTechnologyList(int $limit = 8): array
    {
        if (! Schema::hasTable('compiled_device_records')) {
            return [];
        }

        $technologies = $this->centralCuratedRecordQuery()
            ->whereNotNull('technology')
            ->where('technology', '<>', '')
            ->distinct()
            ->orderBy('technology')
            ->limit($limit)
            ->pluck('technology')
            ->filter()
            ->values()
            ->all();

        return $technologies ?: ['TOPCon', 'HJT', 'Bifacial', 'Double Glass', 'String Inverters', 'Central Inverters'];
    }

    /**
     * @return array<string,mixed>
     */
    private function recordSummary(CompiledDeviceRecord $record): array
    {
        return [
            'id' => $record->id,
            'uuid' => $record->uuid,
            'device_type' => $record->device_type,
            'manufacturer' => $record->manufacturer,
            'series' => $record->series,
            'family' => $record->family,
            'technology' => $record->technology,
            'model_series' => $record->model_series,
            'model_name' => $record->model_name,
            'display_name' => $record->display_name ?: $record->model_name ?: $record->model_series ?: 'Engineering Record',
            'power_class_w' => $record->power_class_w,
            'power_class_kw' => $record->power_class_kw,
            'status' => $record->status,
            'review_status' => $record->review_status,
            'validation_status' => $record->validation_status,
            'validation_grade' => $record->validation_grade,
            'validation_score' => $record->validation_score,
            'source_type' => $record->source_type,
            'source_label' => match ($record->source_type) {
                'tenant_private' => 'My Private Datasets',
                'pvsyst_import' => 'PVSyst Import',
                'partner_submitted' => 'Partner Submission',
                default => 'LineWatt Library',
            },
            'inverter_device_type' => $record->metadata['inverter_device_type'] ?? null,
            'compiler_version' => $record->compiler_version,
            'created_at' => $record->created_at?->toIso8601String(),
            'submitted_by' => $record->metadata['submitted_by'] ?? null,
            'submitted_at' => $record->metadata['submitted_at'] ?? null,
            'datasheet' => $record->datasheet ? [
                'uuid' => $record->datasheet->uuid,
                'product_name' => $record->datasheet->product_name,
                'original_filename' => $record->datasheet->datasheet_original_filename,
                'status' => $record->datasheet->status,
            ] : null,
            'href' => route('records.show', ['record' => $record->uuid ?: $record->id]),
            'review_href' => match ($record->source_type) {
                'tenant_private', 'pvsyst_import' => route('my-library.records.review', ['record' => $record->uuid ?: $record->id]),
                'partner_submitted' => route('partner.submissions.review', ['record' => $record->uuid ?: $record->id]),
                default => route('central-library.review', ['record' => $record->uuid ?: $record->id]),
            },
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function compiledJsonSummary(CompiledDeviceRecord $record): ?array
    {
        $decoded = $this->readCompiledJson($record);

        if ($decoded === null) {
            if (! $record->compiled_disk || ! $record->compiled_path) {
                return null;
            }

            return [
                'available' => false,
                'message' => 'Compiled Engineering Record artifact was not found or could not be decoded.',
            ];
        }

        return [
            'available' => true,
            'manufacturer' => $decoded['manufacturer'] ?? null,
            'series' => $decoded['series'] ?? null,
            'model_count' => is_countable($decoded['models'] ?? null) ? count($decoded['models']) : null,
            'has_electrical' => ! empty($decoded['electrical_stc']) || ! empty($decoded['dc_input']) || ! empty($decoded['ac_output']),
            'has_validation' => ! empty($decoded['validation']),
            'top_level_sections' => array_values(array_slice(array_keys($decoded), 0, 18)),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    private function compiledJsonPresentation(CompiledDeviceRecord $record): ?array
    {
        $decoded = $this->readCompiledJson($record);

        if ($decoded === null) {
            return null;
        }

        $presentation = [
            'overview' => $this->onlyPresent($decoded, [
                'manufacturer',
                'series',
                'family',
                'technology',
                'model_series',
                'model_name',
                'display_name',
                'power_class_w',
                'power_class_kw',
                'device_type',
                'unsupported_reason',
            ]),
            'electrical' => [
                'electrical_stc' => $decoded['electrical_stc'] ?? null,
                'dc_input' => $decoded['dc_input'] ?? null,
                'ac_output' => $decoded['ac_output'] ?? null,
                'rated_power_conditions' => $decoded['rated_power_conditions'] ?? null,
            ],
            'general' => [
                'mechanical' => $decoded['mechanical'] ?? null,
                'operating_conditions' => $decoded['operating_conditions'] ?? null,
                'temperature_characteristics' => $decoded['temperature_characteristics'] ?? null,
                'central_specific' => $decoded['central_specific'] ?? null,
                'packaging' => $decoded['packaging'] ?? null,
                'certifications' => $decoded['certifications'] ?? null,
            ],
            'protection' => $decoded['protection'] ?? null,
            'warranty' => $decoded['warranty'] ?? null,
            'applications' => $decoded['applications'] ?? null,
            'validation' => $decoded['validation'] ?? null,
            'source' => [
                'source_metadata' => $decoded['source_metadata'] ?? null,
                'golden_metadata' => $decoded['golden_metadata'] ?? null,
                'extraction_warnings' => $decoded['extraction_warnings'] ?? [],
                'compiled_disk' => $record->compiled_disk,
                'compiled_path' => $record->compiled_path,
                'datasheet' => $record->datasheet ? [
                    'disk' => $record->datasheet->datasheet_disk,
                    'path' => $record->datasheet->datasheet_path,
                    'original_filename' => $record->datasheet->datasheet_original_filename,
                    'sha256' => $record->datasheet->datasheet_sha256,
                ] : null,
            ],
        ];

        if (config('linewatt-library.debug')) {
            $presentation['raw_json'] = $decoded;
        }

        return $presentation;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function readCompiledJson(CompiledDeviceRecord $record): ?array
    {
        $review = $record->metadata['review_artifact'] ?? null;

        if (is_array($review) && ! empty($review['disk']) && ! empty($review['path'])) {
            $reviewDisk = Storage::disk($review['disk']);

            if ($reviewDisk->exists($review['path'])) {
                $reviewDecoded = json_decode($reviewDisk->get($review['path']), true);

                if (is_array($reviewDecoded) && is_array($reviewDecoded['reviewed_payload'] ?? null)) {
                    return $reviewDecoded['reviewed_payload'];
                }
            }
        }

        if (! $record->compiled_disk || ! $record->compiled_path) {
            return null;
        }

        $disk = Storage::disk($record->compiled_disk);

        if (! $disk->exists($record->compiled_path)) {
            return [
                'available' => false,
                'message' => 'Compiled Engineering Record artifact was not found in storage.',
            ];
        }

        $decoded = json_decode($disk->get($record->compiled_path), true);

        if (! is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    /**
     * @param  string[]  $keys
     * @return array<string,mixed>
     */
    private function onlyPresent(array $payload, array $keys): array
    {
        $values = [];

        foreach ($keys as $key) {
            if (array_key_exists($key, $payload) && $payload[$key] !== null && $payload[$key] !== '') {
                $values[$key] = $payload[$key];
            }
        }

        return $values;
    }
}
