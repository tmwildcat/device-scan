<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LineWatt\Concerns\BuildsEngineeringRecordPayloads;
use App\LineWatt\Access\Entitlement;
use App\LineWatt\Access\EntitlementChecker;
use App\LineWatt\Access\LineWattRole;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\ManufacturerCompany;
use App\Models\ManufacturerSupportingDocument;
use App\Models\PowerSearchOption;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class EngineeringSearchController extends Controller
{
    use BuildsEngineeringRecordPayloads;

    public function __invoke(Request $request): Response
    {
        return $this->searchBuilder($request, 'all');
    }

    public function modules(Request $request): Response
    {
        return $this->searchBuilder($request, 'modules');
    }

    public function inverters(Request $request): Response
    {
        return $this->searchBuilder($request, 'inverters');
    }

    public function manufacturer(Request $request, string $manufacturer): Response
    {
        $manufacturer = trim(urldecode($manufacturer));
        $activeTab = (string) $request->query('tab', 'modules');
        $activeTab = in_array($activeTab, ['modules', 'inverters', 'others'], true) ? $activeTab : 'modules';
        $tabs = [
            'modules' => 0,
            'inverters' => 0,
            'others' => 0,
        ];
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

        if ($manufacturer !== '' && Schema::hasTable('compiled_device_records')) {
            $builder = $this->manufacturerDirectoryRecordQuery($request)
                ->where(function (Builder $query) use ($manufacturer): void {
                    $query
                        ->whereRaw('LOWER(manufacturer) = ?', [mb_strtolower($manufacturer)])
                        ->orWhereRaw("LOWER(REPLACE(manufacturer, ' ', '-')) = ?", [mb_strtolower($manufacturer)]);
                });

            $displayManufacturer = (clone $builder)
                ->whereNotNull('manufacturer')
                ->orderBy('manufacturer')
                ->value('manufacturer');

            if (is_string($displayManufacturer) && $displayManufacturer !== '') {
                $manufacturer = $displayManufacturer;
            }

            $tabs = [
                'modules' => (clone $builder)->where('device_type', 'module')->count(),
                'inverters' => (clone $builder)->where('device_type', 'inverter')->count(),
                'others' => (clone $builder)->whereNotIn('device_type', ['module', 'inverter'])->count(),
            ];

            $recordsBuilder = clone $builder;
            if ($activeTab === 'modules') {
                $recordsBuilder->where('device_type', 'module');
            } elseif ($activeTab === 'inverters') {
                $recordsBuilder->where('device_type', 'inverter');
            } else {
                $recordsBuilder->whereNotIn('device_type', ['module', 'inverter']);
            }

            $records = $recordsBuilder
                ->orderByDesc('updated_at')
                ->orderByDesc('created_at')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (CompiledDeviceRecord $record): array => $this->manufacturerRecordSummary($request, $record))
                ->toArray();
        }

        return Inertia::render('LineWatt/ManufacturerProducts', [
            'manufacturer' => $manufacturer,
            'profile' => $this->publicManufacturerProfile($request, $manufacturer),
            'isLibraryStaffView' => $this->isLibraryStaff($request),
            'activeTab' => $activeTab,
            'tabs' => $tabs,
            'records' => $records,
        ]);
    }

    public function manufacturersIndex(Request $request): Response
    {
        $letter = strtoupper(substr((string) $request->query('letter', 'A'), 0, 1));
        $letter = preg_match('/^[A-Z]$/', $letter) === 1 ? $letter : 'A';
        $activeTab = (string) $request->query('tab', 'modules');
        $activeTab = in_array($activeTab, ['modules', 'inverters', 'others'], true) ? $activeTab : 'modules';
        $manufacturers = [];
        $tabs = ['modules' => 0, 'inverters' => 0, 'others' => 0];

        if (Schema::hasTable('compiled_device_records')) {
            $base = $this->manufacturerDirectoryRecordQuery($request)
                ->whereNotNull('manufacturer')
                ->where('manufacturer', '<>', '');

            $tabs = [
                'modules' => (clone $base)->where('device_type', 'module')->distinct('manufacturer')->count('manufacturer'),
                'inverters' => (clone $base)->where('device_type', 'inverter')->distinct('manufacturer')->count('manufacturer'),
                'others' => (clone $base)->whereNotIn('device_type', ['module', 'inverter'])->distinct('manufacturer')->count('manufacturer'),
            ];

            $builder = clone $base;
            if ($activeTab === 'modules') {
                $builder->where('device_type', 'module');
            } elseif ($activeTab === 'inverters') {
                $builder->where('device_type', 'inverter');
            } else {
                $builder->whereNotIn('device_type', ['module', 'inverter']);
            }

            $manufacturers = $builder
                ->whereRaw('UPPER(SUBSTRING(manufacturer, 1, 1)) = ?', [$letter])
                ->selectRaw('manufacturer, COUNT(*) as records_count')
                ->groupBy('manufacturer')
                ->orderBy('manufacturer')
                ->paginate(40)
                ->withQueryString()
                ->through(fn (CompiledDeviceRecord $record): array => [
                    'manufacturer' => (string) $record->manufacturer,
                    'records_count' => (int) $record->records_count,
                    'href' => route('manufacturers.show', ['manufacturer' => $record->manufacturer, 'tab' => $activeTab]),
                ])
                ->toArray();
        }

        return Inertia::render('LineWatt/ManufacturerIndex', [
            'letter' => $letter,
            'activeTab' => $activeTab,
            'tabs' => $tabs,
            'manufacturers' => $manufacturers,
            'alphabet' => range('A', 'Z'),
        ]);
    }

    private function searchBuilder(Request $request, string $mode): Response
    {
        $filters = $this->filtersFromRequest($request);
        $filters['tab'] = $mode;
        $deviceTypeCounts = ['all' => 0, 'module' => 0, 'inverter' => 0];
        $filterOptions = [
            'technologies' => [],
            'validation_grades' => [],
            'inverter_device_types' => [],
        ];

        if (Schema::hasTable('compiled_device_records')) {
            $stats = $this->centralLibraryStats();
            $deviceTypeCounts = [
                'all' => $stats['total'],
                'module' => $stats['modules'],
                'inverter' => $stats['inverters'],
            ];
            $filterOptions = $this->filterOptions();
        }

        return Inertia::render('LineWatt/EngineeringSearch', [
            'mode' => $mode,
            'filters' => $filters,
            'deviceTypeCounts' => $deviceTypeCounts,
            'filterOptions' => $filterOptions,
            'powerSearch' => $this->powerSearchPayload($mode),
        ]);
    }

    public function results(Request $request): Response
    {
        $filters = $this->filtersFromRequest($request);
        $sort = $this->sortFromRequest($request);
        $deviceTypeCounts = ['all' => 0, 'module' => 0, 'inverter' => 0];

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
            $stats = $this->centralLibraryStats();
            $deviceTypeCounts = [
                'all' => $stats['total'],
                'module' => $stats['modules'],
                'inverter' => $stats['inverters'],
            ];

            $builder = $this->accessibleRecordQuery($request, $filters['scope']);
            $this->applySearchFilters($builder, $filters);
            $this->applySort($builder, $sort, $filters['tab']);

            if (($filters['engineering_query_parsed'] ?? '0') === '1' && (clone $builder)->count() === 0) {
                $queryFilters = $this->relaxedParsedFilters($filters);
                $builder = $this->accessibleRecordQuery($request, $filters['scope']);
                $this->applySearchFilters($builder, $queryFilters);
                $this->applySort($builder, $sort, $queryFilters['tab']);
            }

            $records = $builder
                ->paginate(20)
                ->withQueryString()
                ->through(fn (CompiledDeviceRecord $record): array => $this->recordSummary($record))
                ->toArray();
        }

        return Inertia::render('LineWatt/EngineeringSearchResults', [
            'filters' => $filters,
            'sort' => $sort,
            'deviceTypeCounts' => $deviceTypeCounts,
            'emptyState' => $this->emptyState($records['data'], $deviceTypeCounts, $filters),
            'records' => $records,
            'powerSearch' => $this->powerSearchPayload($filters['tab'] ?? 'all', $filters['power_tags'] ?? []),
        ]);
    }

    public function manufacturers(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        if (in_array($request->query('source'), ['datasheets', 'engineering-data'], true)) {
            abort_unless(app(EntitlementChecker::class)->has($request->user(), Entitlement::CENTRAL_MANAGE), 403);

            if ($request->query('source') === 'engineering-data') {
                if (! Schema::hasTable('compiled_device_records')) {
                    return response()->json([]);
                }

                $builder = CompiledDeviceRecord::query()
                    ->whereIn('source_type', ['central_curated', 'partner_submitted'])
                    ->whereNotNull('manufacturer')
                    ->where('manufacturer', '<>', '')
                    ->whereRaw('LOWER(manufacturer) LIKE ?', ['%' . mb_strtolower($query) . '%']);

                $deviceType = (string) $request->query('device_type', '');
                if (in_array($deviceType, ['module', 'inverter'], true)) {
                    $builder->where('device_type', $deviceType);
                }

                $manufacturers = $builder
                    ->select('manufacturer')
                    ->selectRaw('count(*) as records_count')
                    ->groupBy('manufacturer')
                    ->orderBy('manufacturer')
                    ->limit(20)
                    ->get()
                    ->map(fn (CompiledDeviceRecord $record): array => [
                        'label' => (string) $record->manufacturer,
                        'value' => (string) $record->manufacturer,
                        'url' => route('admin.library.engineering-data', [
                            'manufacturer' => $record->manufacturer,
                        ]),
                        'count' => (int) $record->records_count,
                    ])
                    ->all();

                return response()->json($manufacturers);
            }

            if (! Schema::hasTable('device_datasheets')) {
                return response()->json([]);
            }

            $builder = DeviceDatasheet::query()
                ->whereNotNull('manufacturer')
                ->where('manufacturer', '<>', '')
                ->whereRaw('LOWER(manufacturer) LIKE ?', ['%' . mb_strtolower($query) . '%']);

            $deviceType = (string) $request->query('device_type', '');
            if (in_array($deviceType, ['module', 'inverter'], true)) {
                $builder->where('device_type', $deviceType);
            }

            $manufacturers = $builder
                ->select('manufacturer')
                ->selectRaw('count(*) as datasheets_count')
                ->groupBy('manufacturer')
                ->orderBy('manufacturer')
                ->limit(20)
                ->get()
                ->map(fn (DeviceDatasheet $datasheet): array => [
                    'label' => (string) $datasheet->manufacturer,
                    'value' => (string) $datasheet->manufacturer,
                    'url' => route('admin.library.datasheets.all', [
                        'tab' => $deviceType === 'inverter' ? 'inverters' : 'modules',
                        'manufacturer' => $datasheet->manufacturer,
                    ]),
                    'count' => (int) $datasheet->datasheets_count,
                ])
                ->all();

            return response()->json($manufacturers);
        }

        if (! Schema::hasTable('compiled_device_records')) {
            return response()->json([]);
        }

        if ($request->query('source') === 'manufacturer-directory') {
            $manufacturers = collect();

            if (Schema::hasTable('manufacturer_companies')) {
                $manufacturers = $manufacturers->merge(
                    ManufacturerCompany::query()
                        ->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($query) . '%'])
                        ->orderBy('name')
                        ->limit(20)
                        ->pluck('name')
                );
            }

            $recordManufacturers = $this->manufacturerDirectoryRecordQuery($request)
                ->whereNotNull('manufacturer')
                ->where('manufacturer', '<>', '')
                ->whereRaw('LOWER(manufacturer) LIKE ?', ['%' . mb_strtolower($query) . '%'])
                ->distinct()
                ->orderBy('manufacturer')
                ->limit(20)
                ->pluck('manufacturer');

            $manufacturers = $manufacturers
                ->merge($recordManufacturers)
                ->filter()
                ->unique(fn (string $manufacturer): string => mb_strtolower($manufacturer))
                ->sort()
                ->take(20)
                ->values()
                ->map(fn (string $manufacturer): array => [
                    'label' => $manufacturer,
                    'value' => $manufacturer,
                    'url' => route('manufacturers.show', ['manufacturer' => $manufacturer]),
                ])
                ->all();

            return response()->json($manufacturers);
        }

        $builder = $this->accessibleRecordQuery($request, $this->scopeFromRequest($request))
            ->whereNotNull('manufacturer')
            ->where('manufacturer', '<>', '')
            ->whereRaw('LOWER(manufacturer) LIKE ?', ['%' . mb_strtolower($query) . '%']);

        $deviceType = (string) $request->query('device_type', '');
        if (in_array($deviceType, ['module', 'inverter'], true)) {
            $builder->where('device_type', $deviceType);
        }

        $manufacturers = $builder
            ->distinct()
            ->orderBy('manufacturer')
            ->limit(20)
            ->pluck('manufacturer')
            ->filter()
            ->values()
            ->map(fn (string $manufacturer): array => [
                'label' => $manufacturer,
                'value' => $manufacturer,
                'url' => route('manufacturers.show', ['manufacturer' => $manufacturer]),
            ])
            ->all();

        return response()->json($manufacturers);
    }

    /**
     * @param  Builder<CompiledDeviceRecord>  $builder
     * @param  array<string,string>  $filters
     */
    private function applySearchFilters(Builder $builder, array $filters): void
    {
        if ($filters['tab'] === 'modules') {
            $builder->where('device_type', 'module');
        } elseif ($filters['tab'] === 'inverters') {
            $builder->where('device_type', 'inverter');
        } elseif (in_array($filters['device_type'], ['string_inverter', 'hybrid_inverter', 'central_inverter', 'storage_inverter'], true)) {
            $builder
                ->where('device_type', 'inverter')
                ->where('metadata->inverter_device_type', $filters['device_type']);
        } elseif (in_array($filters['device_type'], ['module', 'inverter'], true)) {
            $builder->where('device_type', $filters['device_type']);
        }

        foreach ($this->searchTerms($filters) as $term) {
            $builder->where(function (Builder $search) use ($term): void {
                $like = '%' . mb_strtolower($term) . '%';
                $search
                    ->whereRaw('LOWER(manufacturer) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(series) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(family) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(technology) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(model_series) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(model_name) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(display_name) LIKE ?', [$like]);
            });
        }

        if ($filters['manufacturer'] !== '') {
            $builder->whereRaw('LOWER(manufacturer) LIKE ?', ['%' . mb_strtolower($this->manufacturerSearchTerm($filters['manufacturer'])) . '%']);
        }

        if ($filters['technology'] !== '' && ($filters['parsed_technology'] ?? '0') !== '1') {
            $builder->where(function (Builder $technology) use ($filters): void {
                $technology
                    ->whereRaw('LOWER(technology) LIKE ?', ['%' . mb_strtolower($filters['technology']) . '%'])
                    ->orWhereNull('technology');
            });
        }

        if ($filters['validation_grade'] !== '') {
            $builder->where('validation_grade', $filters['validation_grade']);
        }

        if (($filters['needs_review'] ?? '') === '1') {
            $builder->where(function (Builder $review): void {
                $review
                    ->whereIn('status', ['review_required', 'compiled'])
                    ->orWhereIn('metadata->review_status', ['pending_review', 'flagged']);
            });
        }

        if ($filters['model_series'] !== '') {
            $builder->where('model_series', 'like', "%{$filters['model_series']}%");
        }

        if ($filters['power_min'] !== '') {
            $column = $filters['tab'] === 'inverters' ? 'power_class_kw' : 'power_class_w';
            $builder->where($column, '>=', (float) $filters['power_min']);
        }

        if ($filters['power_max'] !== '') {
            $column = $filters['tab'] === 'inverters' ? 'power_class_kw' : 'power_class_w';
            $builder->where($column, '<=', (float) $filters['power_max']);
        }

        if ($filters['tab'] === 'inverters' && $filters['inverter_device_type'] !== '') {
            $builder->where('metadata->inverter_device_type', $filters['inverter_device_type']);
        }

        foreach (($filters['power_tags'] ?? []) as $tag) {
            $builder->whereHas('powerSearchOptions', fn (Builder $tagQuery) => $tagQuery->where('slug', $tag));
        }
    }

    /**
     * @return array<string,string>
     */
    private function filtersFromRequest(Request $request): array
    {
        $tab = (string) $request->query('tab', 'all');
        $tab = in_array($tab, ['all', 'modules', 'inverters'], true) ? $tab : 'all';

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'tab' => $tab,
            'scope' => $this->scopeFromRequest($request),
            'device_type' => (string) $request->query('device_type', ''),
            'manufacturer' => trim((string) $request->query('manufacturer', '')),
            'model_series' => trim((string) $request->query('model_series', '')),
            'technology' => trim((string) $request->query('technology', '')),
            'validation_grade' => trim((string) $request->query('validation_grade', '')),
            'power_min' => trim((string) $request->query('power_min', '')),
            'power_max' => trim((string) $request->query('power_max', '')),
            'inverter_device_type' => trim((string) $request->query('inverter_device_type', '')),
            'bifacial' => trim((string) $request->query('bifacial', '')),
            'double_glass' => trim((string) $request->query('double_glass', '')),
            'maximum_system_voltage' => trim((string) $request->query('maximum_system_voltage', '')),
            'has_dc_switch' => trim((string) $request->query('has_dc_switch', '')),
            'has_dc_spd' => trim((string) $request->query('has_dc_spd', '')),
            'has_ac_spd' => trim((string) $request->query('has_ac_spd', '')),
            'has_afci' => trim((string) $request->query('has_afci', '')),
            'has_rcmu' => trim((string) $request->query('has_rcmu', '')),
            'mppt_count' => trim((string) $request->query('mppt_count', '')),
            'needs_review' => trim((string) $request->query('needs_review', '')),
            'power_tags' => collect($request->query('power_tags', []))
                ->map(fn (mixed $tag): string => trim((string) $tag))
                ->filter()
                ->unique()
                ->values()
                ->all(),
            'parsed_terms' => '',
            'engineering_query_parsed' => '0',
            'parsed_technology' => '0',
        ];

        return $this->applyEngineeringQueryParser($filters);
    }

    /**
     * @param  array<string,string>  $filters
     * @return array<string,string>
     */
    private function applyEngineeringQueryParser(array $filters): array
    {
        if ($filters['q'] === '') {
            return $filters;
        }

        $query = mb_strtolower($filters['q']);
        $residual = $query;
        $recognized = false;

        if ($filters['tab'] === 'all' && $filters['device_type'] === '') {
            if (preg_match('/\b(module|panel|bifacial|topcon|hjt|mono|glass|(\d{3,4})\s*w)\b/u', $query) === 1) {
                $filters['tab'] = 'modules';
            }

            if (preg_match('/\b(inverter|hybrid|mppt|spd|afci|rcmu|(\d+(?:\.\d+)?)\s*kw)\b/u', $query) === 1) {
                $filters['tab'] = 'inverters';
            }
        }

        if (preg_match('/\b(\d{3,4})\s*w(?:att|p)?\b/u', $query, $match) === 1 && $filters['power_min'] === '' && $filters['power_max'] === '') {
            $power = (int) $match[1];
            $filters['power_min'] = (string) max(0, $power - 10);
            $filters['power_max'] = (string) ($power + 10);
            $residual = str_replace($match[0], ' ', $residual);
            $recognized = true;
        }

        if (preg_match('/\b(\d+(?:\.\d+)?)\s*kw\b/u', $query, $match) === 1 && $filters['power_min'] === '' && $filters['power_max'] === '') {
            $power = (float) $match[1];
            $filters['power_min'] = (string) max(0, $power - 10);
            $filters['power_max'] = (string) ($power + 10);
            $residual = str_replace($match[0], ' ', $residual);
            $recognized = true;
        }

        foreach ($this->longestAliasesFirst($this->manufacturerAliases()) as $alias => $manufacturer) {
            if ($filters['manufacturer'] === '' && preg_match('/\b' . preg_quote($alias, '/') . '\b/u', $query) === 1) {
                $filters['manufacturer'] = $manufacturer;
                $recognized = true;
            }

            $residual = preg_replace('/\b' . preg_quote($alias, '/') . '\b/u', ' ', $residual) ?? $residual;
        }

        foreach ($this->longestAliasesFirst($this->technologyAliases()) as $alias => $technology) {
            if ($filters['technology'] === '' && preg_match('/\b' . preg_quote($alias, '/') . '\b/u', $query) === 1) {
                $filters['technology'] = $technology;
                $filters['parsed_technology'] = '1';
                $recognized = true;
            }

            $residual = preg_replace('/\b' . preg_quote($alias, '/') . '\b/u', ' ', $residual) ?? $residual;
        }

        foreach ($this->longestAliasesFirst($this->inverterTypeAliases()) as $alias => $deviceType) {
            if (preg_match('/\b' . preg_quote($alias, '/') . '\b/u', $query) === 1) {
                $filters['tab'] = 'inverters';
                $filters['device_type'] = 'inverter';
                $filters['inverter_device_type'] = $deviceType;
                $recognized = true;
            }

            $residual = preg_replace('/\b' . preg_quote($alias, '/') . '\b/u', ' ', $residual) ?? $residual;
        }

        if (str_contains($query, 'bifacial') && $filters['bifacial'] === '') {
            $filters['bifacial'] = '1';
            $residual = preg_replace('/\bbifacial\b/u', ' ', $residual) ?? $residual;
            $recognized = true;
        }

        if (preg_match('/\b(\d+)\s*mppt\b/u', $query, $match) === 1 && $filters['mppt_count'] === '') {
            $filters['tab'] = 'inverters';
            $filters['mppt_count'] = $match[1];
            $residual = str_replace($match[0], ' ', $residual);
            $recognized = true;
        }

        foreach (['has_dc_spd' => 'dc spd', 'has_ac_spd' => 'ac spd', 'has_afci' => 'afci', 'has_rcmu' => 'rcmu'] as $field => $needle) {
            if (str_contains($query, $needle) && $filters[$field] === '') {
                $filters[$field] = '1';
                $residual = str_replace($needle, ' ', $residual);
                $recognized = true;
            }
        }

        $filters['parsed_terms'] = trim((string) preg_replace('/\s+/u', ' ', $residual));
        $filters['engineering_query_parsed'] = $recognized ? '1' : '0';

        return $filters;
    }

    private function scopeFromRequest(Request $request): string
    {
        $scope = (string) $request->query('scope', 'central');

        return in_array($scope, ['central', 'my-library', 'both'], true) ? $scope : 'central';
    }

    /**
     * @param  array<string,string>  $filters
     * @return array<string,string>
     */
    private function relaxedParsedFilters(array $filters): array
    {
        if ($filters['tab'] === 'inverters') {
            $filters['power_min'] = '';
            $filters['power_max'] = '';
            $filters['inverter_device_type'] = '';
        }

        return $filters;
    }

    /**
     * @param  array<string,string>  $filters
     * @return string[]
     */
    private function searchTerms(array $filters): array
    {
        $terms = ($filters['engineering_query_parsed'] ?? '0') === '1' ? $filters['parsed_terms'] : $filters['q'];

        return collect(preg_split('/\s+/u', mb_strtolower($terms)) ?: [])
            ->map(fn (string $term): string => trim($term))
            ->filter(fn (string $term): bool => mb_strlen($term) >= 2 && ! in_array($term, $this->ignoredSearchTokens(), true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return string[]
     */
    private function ignoredSearchTokens(): array
    {
        return ['w', 'wp', 'kw', 'module', 'modules', 'panel', 'panels', 'inverter', 'inverters', 'with', 'and', 'at'];
    }

    private function manufacturerSearchTerm(string $manufacturer): string
    {
        $lower = mb_strtolower($manufacturer);

        foreach ($this->manufacturerAliases() as $alias => $canonical) {
            if (mb_strtolower($canonical) === $lower) {
                return $alias;
            }
        }

        return $manufacturer;
    }

    /**
     * @param  array<string,string>  $aliases
     * @return array<string,string>
     */
    private function longestAliasesFirst(array $aliases): array
    {
        uksort($aliases, fn (string $left, string $right): int => mb_strlen($right) <=> mb_strlen($left));

        return $aliases;
    }

    /**
     * @return array<string,string>
     */
    private function manufacturerAliases(): array
    {
        return [
            'adani' => 'Adani',
            'astronergy' => 'Astronergy',
            'canadian' => 'Canadian Solar',
            'canadian solar' => 'Canadian Solar',
            'first solar' => 'First Solar',
            'fronius' => 'Fronius',
            'futurasun' => 'FuturaSun',
            'growatt' => 'Growatt',
            'huawei' => 'Huawei',
            'ja solar' => 'JA Solar',
            'jasolar' => 'JA Solar',
            'jinko' => 'Jinko Solar',
            'jinko solar' => 'Jinko Solar',
            'longi' => 'LONGi',
            'maxeon' => 'Maxeon',
            'rec' => 'REC',
            'risen' => 'Risen',
            'sma' => 'SMA',
            'sungrow' => 'Sungrow',
            'trina' => 'Trina Solar',
            'trina solar' => 'Trina Solar',
            'vikram' => 'Vikram Solar',
            'vikram solar' => 'Vikram Solar',
        ];
    }

    /**
     * @return array<string,string>
     */
    private function technologyAliases(): array
    {
        return [
            'topcon' => 'TOPCon',
            'top con' => 'TOPCon',
            'hjt' => 'HJT',
            'heterojunction' => 'HJT',
            'mono' => 'Mono',
            'monocrystalline' => 'Mono',
            'perc' => 'PERC',
            'thin film' => 'Thin Film',
            'thin-film' => 'Thin Film',
            'cdte' => 'CdTe',
        ];
    }

    /**
     * @return array<string,string>
     */
    private function inverterTypeAliases(): array
    {
        return [
            'string' => 'string_inverter',
            'hybrid' => 'hybrid_inverter',
            'central' => 'central_inverter',
            'storage' => 'storage_inverter',
            'pcs' => 'pcs',
        ];
    }

    /**
     * @return Builder<CompiledDeviceRecord>
     */
    private function accessibleRecordQuery(Request $request, string $scope): Builder
    {
        $builder = CompiledDeviceRecord::query()->with('datasheet');

        if ($scope === 'my-library') {
            return $this->privateRecordQuery($builder, $request);
        }

        if ($scope === 'both' && $request->user()) {
            return $builder->where(function (Builder $query) use ($request): void {
                $query
                    ->where(function (Builder $central): void {
                        $central
                            ->where('source_type', 'central_curated')
                            ->where('status', 'published');
                    })
                    ->orWhere(function (Builder $private) use ($request): void {
                        $this->applyPrivateRecordScope($private, $request);
                    });
            });
        }

        return $this->centralCuratedRecordQuery();
    }

    private function manufacturerDirectoryRecordQuery(Request $request): Builder
    {
        if ($this->isLibraryStaff($request)) {
            return CompiledDeviceRecord::query()
                ->with('datasheet')
                ->whereIn('source_type', ['central_curated', 'partner_submitted'])
                ->whereNotIn('status', ['discontinued', 'replaced', 'archived']);
        }

        return $this->centralCuratedRecordQuery();
    }

    private function manufacturerRecordSummary(Request $request, CompiledDeviceRecord $record): array
    {
        $summary = $this->recordSummary($record);

        if ($this->isLibraryStaff($request)) {
            $summary['href'] = $request->user()?->role === LineWattRole::LIBRARY_PUBLISHER
                ? route('publisher.review.show', ['record' => $record->uuid ?: $record->id])
                : route('admin.library.review', ['record' => $record->uuid ?: $record->id]);
            $summary['review_href'] = $summary['href'];
        }

        return $summary;
    }

    /**
     * @param  Builder<CompiledDeviceRecord>  $builder
     * @return Builder<CompiledDeviceRecord>
     */
    private function privateRecordQuery(Builder $builder, Request $request): Builder
    {
        if (! $request->user()) {
            return $builder->whereRaw('1 = 0');
        }

        $this->applyPrivateRecordScope($builder, $request);

        return $builder;
    }

    /**
     * @param  Builder<CompiledDeviceRecord>  $builder
     */
    private function applyPrivateRecordScope(Builder $builder, Request $request): void
    {
        $builder
            ->whereIn('source_type', ['tenant_private', 'pvsyst_import'])
            ->where(function (Builder $tenant) use ($request): void {
                $tenant
                    ->whereNull('tenant_id')
                    ->orWhere('tenant_id', $request->user()?->id);
            });
    }

    private function isLibraryStaff(Request $request): bool
    {
        return in_array($request->user()?->role, LineWattRole::platformRoles(), true);
    }

    /**
     * @return array<string,mixed>
     */
    private function publicManufacturerProfile(Request $request, string $manufacturer): array
    {
        $company = $this->manufacturerCompanyForPublicPage($manufacturer);
        $metadata = $company?->metadata ?? [];
        $name = $company?->name ?: $manufacturer;
        $names = $this->publicManufacturerNames($company, $manufacturer);

        return [
            'company_name' => $name,
            'logo_href' => null,
            'description' => $metadata['short_description'] ?? 'Manufacturer profile details are being maintained in LineWatt Library.',
            'technologies' => $metadata['primary_technologies'] ?? $this->manufacturerTechnologies($names),
            'product_categories' => $this->manufacturerProductCategories($names),
            'factory_summary' => [
                'known_factories' => $company && Schema::hasTable('manufacturer_factory_locations') ? $company->factoryLocations()->count() : 0,
                'primary_country' => $metadata['primary_manufacturing_country'] ?? $metadata['headquarters_country'] ?? 'Pending',
                'certification_status' => $metadata['factory_certification_status'] ?? 'Pending',
            ],
            'distribution_summary' => [
                'countries' => $company && Schema::hasTable('manufacturer_distribution_countries') ? $company->distributionCountries()->count() : 0,
                'priority_regions' => $metadata['priority_regions'] ?? ['Pending'],
                'channel_model' => $metadata['channel_model'] ?? 'Pending',
            ],
            'latest_datasheets' => $this->publicLatestDatasheets($names),
            'company_documents' => $this->publicCompanyDocuments($company),
            'contacts' => $this->publicContactRows($metadata),
        ];
    }

    private function manufacturerCompanyForPublicPage(string $manufacturer): ?ManufacturerCompany
    {
        if (! Schema::hasTable('manufacturer_companies')) {
            return null;
        }

        $slug = ManufacturerCompany::slugFor($manufacturer);
        $lower = mb_strtolower($manufacturer);

        return ManufacturerCompany::query()
            ->where('slug', $slug)
            ->orWhereRaw('LOWER(name) = ?', [$lower])
            ->first();
    }

    /**
     * @return string[]
     */
    private function publicManufacturerNames(?ManufacturerCompany $company, string $manufacturer): array
    {
        return collect([
            $manufacturer,
            $company?->name,
            $company?->slug,
            ...(array) ($company?->metadata['aliases'] ?? []),
        ])
            ->filter(fn ($name): bool => is_string($name) && trim($name) !== '')
            ->map(fn (string $name): string => trim($name))
            ->unique(fn (string $name): string => mb_strtolower($name))
            ->values()
            ->all();
    }

    /**
     * @param  string[]  $names
     * @return string[]
     */
    private function manufacturerProductCategories(array $names): array
    {
        if (! Schema::hasTable('compiled_device_records')) {
            return ['Engineering Records'];
        }

        $types = $this->centralCuratedRecordQuery()
            ->whereIn('manufacturer', $names)
            ->select('device_type')
            ->distinct()
            ->pluck('device_type')
            ->filter()
            ->values();

        return $types
            ->map(fn (string $type): string => match ($type) {
                'module' => 'Solar PV Modules',
                'inverter' => 'Solar Inverters',
                default => ucfirst(str_replace('_', ' ', $type)),
            })
            ->whenEmpty(fn ($items) => $items->push('Engineering Records'))
            ->all();
    }

    /**
     * @param  string[]  $names
     * @return string[]
     */
    private function manufacturerTechnologies(array $names): array
    {
        if (! Schema::hasTable('compiled_device_records')) {
            return ['Renewable energy equipment'];
        }

        return $this->centralCuratedRecordQuery()
            ->whereIn('manufacturer', $names)
            ->whereNotNull('technology')
            ->where('technology', '<>', '')
            ->select('technology')
            ->distinct()
            ->orderBy('technology')
            ->limit(6)
            ->pluck('technology')
            ->whenEmpty(fn ($items) => $items->push('Renewable energy equipment'))
            ->values()
            ->all();
    }

    /**
     * @param  string[]  $names
     * @return array<int,array{title:string,series:?string,status:?string,updated:?string}>
     */
    private function publicLatestDatasheets(array $names): array
    {
        if (! Schema::hasTable('device_datasheets')) {
            return [];
        }

        return DeviceDatasheet::query()
            ->where('source_type', 'central_curated')
            ->where('status', 'published')
            ->whereIn('manufacturer', $names)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (DeviceDatasheet $datasheet): array => [
                'title' => $datasheet->product_name ?: $datasheet->datasheet_original_filename ?: 'Datasheet',
                'series' => $datasheet->series,
                'status' => $datasheet->status,
                'updated' => $datasheet->updated_at?->toDateString(),
            ])
            ->all();
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function publicCompanyDocuments(?ManufacturerCompany $company): array
    {
        if (! $company || ! Schema::hasTable('manufacturer_supporting_documents')) {
            return [];
        }

        return ManufacturerSupportingDocument::query()
            ->where('manufacturer_company_id', $company->id)
            ->where('supporting_document_scope', ManufacturerSupportingDocument::SCOPE_COMPANY)
            ->whereIn('status', ['approved', 'published', 'active'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (ManufacturerSupportingDocument $document): array => [
                'Document title' => $document->title,
                'Category' => $document->category ?: 'Company document',
                'Status' => $document->status ?: 'published',
            ])
            ->all();
    }

    /**
     * @param  array<string,mixed>  $metadata
     * @return array<int,array{label:string,value:string}>
     */
    private function publicContactRows(array $metadata): array
    {
        return collect([
            ['label' => 'Website', 'value' => $metadata['website'] ?? 'Pending'],
            ['label' => 'Sales', 'value' => $metadata['sales_contact_email'] ?? 'Pending'],
            ['label' => 'Technical', 'value' => $metadata['technical_contact_email'] ?? 'Pending'],
        ])
            ->filter(fn (array $row): bool => $row['value'] !== 'Pending')
            ->whenEmpty(fn ($items) => $items->push(['label' => 'Contact', 'value' => 'Contact details pending']))
            ->values()
            ->all();
    }

    private function sortFromRequest(Request $request): string
    {
        $sort = (string) $request->query('sort', 'newest');

        return in_array($sort, ['newest', 'manufacturer', 'power_asc', 'power_desc', 'validation'], true)
            ? $sort
            : 'newest';
    }

    private function applySort(Builder $builder, string $sort, string $tab): void
    {
        match ($sort) {
            'manufacturer' => $builder->orderBy('manufacturer')->orderBy('display_name'),
            'power_asc' => $builder->orderBy($tab === 'inverters' ? 'power_class_kw' : 'power_class_w')->orderBy('manufacturer'),
            'power_desc' => $builder->orderByDesc($tab === 'inverters' ? 'power_class_kw' : 'power_class_w')->orderBy('manufacturer'),
            'validation' => $builder->orderBy('validation_grade')->orderByDesc('validation_score'),
            default => $builder->latest(),
        };
    }

    private function recordSortKey(CompiledDeviceRecord $record): string
    {
        $year = $record->metadata['year']
            ?? $record->metadata['release_year']
            ?? $record->metadata['product_year']
            ?? null;

        return sprintf(
            '%04d-%s-%010d',
            is_numeric($year) ? (int) $year : 0,
            $record->updated_at?->format('YmdHis') ?? $record->created_at?->format('YmdHis') ?? '',
            $record->id
        );
    }

    /**
     * @return array<string,array<int,string>>
     */
    private function filterOptions(): array
    {
        $base = $this->centralCuratedRecordQuery();

        return [
            'technologies' => $this->distinctOptions((clone $base)->where('device_type', 'module'), 'technology'),
            'validation_grades' => $this->distinctOptions(clone $base, 'validation_grade'),
            'inverter_device_types' => (clone $base)
                ->where('device_type', 'inverter')
                ->whereNotNull('metadata->inverter_device_type')
                ->get(['metadata'])
                ->map(fn (CompiledDeviceRecord $record): ?string => $record->metadata['inverter_device_type'] ?? null)
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Builder<CompiledDeviceRecord>  $builder
     * @return string[]
     */
    private function distinctOptions(Builder $builder, string $column): array
    {
        return $builder
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<int,array<string,mixed>>  $records
     * @param  array{all:int,module:int,inverter:int}  $counts
     * @param  array<string,string>  $filters
     * @return array{type:string,title:string,message:string}
     */
    private function emptyState(array $records, array $counts, array $filters): array
    {
        if ($records !== []) {
            return ['type' => 'has_results', 'title' => '', 'message' => ''];
        }

        if ($counts['all'] === 0) {
            return [
                'type' => 'no_central_records',
                'title' => 'No central Engineering Records yet',
                'message' => 'Central curated records will appear here after librarian compilation and publication.',
            ];
        }

        if ($this->hasActiveFilters($filters)) {
            return [
                'type' => 'filters_too_narrow',
                'title' => 'No results for these filters',
                'message' => 'Try clearing one or two filters, widening the power range, or switching product tabs.',
            ];
        }

        return [
            'type' => 'no_results',
            'title' => 'No results',
            'message' => 'No central Engineering Records are available for this product tab yet.',
        ];
    }

    /**
     * @param  array<string,string>  $filters
     */
    private function hasActiveFilters(array $filters): bool
    {
        foreach ($filters as $key => $value) {
            if ($key === 'tab') {
                continue;
            }

            if (is_array($value) && $value !== []) {
                return true;
            }

            if ($value !== '') {
                return true;
            }
        }

        return $filters['tab'] !== 'all';
    }

    /**
     * @param  string[]  $selected
     * @return array<string,mixed>
     */
    private function powerSearchPayload(string $mode, array $selected = []): array
    {
        if (! Schema::hasTable('power_search_options')) {
            return ['categories' => [], 'featured' => [], 'selected' => []];
        }

        $scopes = match ($mode) {
            'modules' => ['all', 'module'],
            'inverters' => ['all', 'inverter'],
            default => ['all', 'module', 'inverter'],
        };

        $options = PowerSearchOption::query()
            ->with('category')
            ->where('is_active', true)
            ->whereIn('scope', $scopes)
            ->orderBy('sort_order')
            ->get();

        $mapped = $options->map(fn (PowerSearchOption $option): array => [
            'label' => $option->label,
            'slug' => $option->slug,
            'category' => $option->category?->name,
            'category_slug' => $option->category?->slug,
            'scope' => $option->scope,
        ]);

        $featuredSlugs = [
            'india',
            'europe',
            'pm-kusum-approved',
            'almm-listed',
            'residential',
            'commercial',
            'rooftop',
            'topcon',
            'bifacial',
            'double-glass',
            '1500v-systems',
            'dc-spd',
            'afci',
            'rcmu',
        ];

        return [
            'categories' => $mapped->groupBy('category')->map(fn ($group) => $group->values()->all())->all(),
            'featured' => $mapped->filter(fn (array $option): bool => in_array($option['slug'], $featuredSlugs, true))->values()->all(),
            'selected' => $mapped->filter(fn (array $option): bool => in_array($option['slug'], $selected, true))->values()->all(),
        ];
    }
}
