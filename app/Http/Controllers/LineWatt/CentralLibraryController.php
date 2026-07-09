<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LineWatt\Concerns\BuildsEngineeringRecordPayloads;
use App\LineWatt\Access\LineWattRole;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\ManufacturerCompany;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class CentralLibraryController extends Controller
{
    use BuildsEngineeringRecordPayloads;

    public function __invoke(): Response
    {
        $summary = [
            'published_records' => 0,
            'pending_review' => 0,
            'validation_warnings' => 0,
            'partner_submissions' => 0,
            'oem_submissions' => 0,
            'changes_requested' => 0,
            'failed_compiles' => 0,
            'recently_published' => 0,
            'manufacturers' => 0,
            'pending_review_records' => [],
            'oem_submission_records' => [],
            'changes_requested_records' => [],
            'failed_compile_datasheets' => [],
            'recent_records' => [],
            'activity' => [
                'subscriber' => [
                    'total' => 0,
                    'added_this_week' => 0,
                    'dropped_this_week' => 0,
                ],
                'oem_partner' => [
                    'total' => 0,
                    'added_this_week' => 0,
                    'dropped_this_week' => 0,
                    'submissions' => 0,
                ],
                'guest' => [
                    'total' => 0,
                    'searches_this_week' => 0,
                    'conversion_intent' => 0,
                ],
                'pdf_downloads' => 0,
                'exports' => 0,
                'high_intent_searches' => 0,
                'recently_onboarded_oems' => 0,
                'subscribers_added' => 0,
            ],
        ];

        if (Schema::hasTable('compiled_device_records')) {
            $centralRecords = CompiledDeviceRecord::query()->where('source_type', 'central_curated');

            $summary['published_records'] = $this->workflowRecordQuery('published')->count();
            $summary['pending_review'] = $this->workflowRecordQuery('pending_approval')->count();
            $summary['oem_submissions'] = $this->workflowRecordQuery('pending_approval', 'oem')->count();
            $summary['changes_requested'] = $this->workflowRecordQuery('changes_requested')->count();
            $summary['validation_warnings'] = $this->workflowRecordQuery('validation_warnings')->count();
            $summary['recently_published'] = $this->workflowRecordQuery('recently_published')->count();
            $summary['manufacturers'] = (clone $centralRecords)->whereNotNull('manufacturer')->distinct('manufacturer')->count('manufacturer');
            $summary['activity']['recently_onboarded_oems'] = (clone $centralRecords)
                ->whereNotNull('manufacturer')
                ->where('created_at', '>=', now()->subDays(30))
                ->distinct('manufacturer')
                ->count('manufacturer');
            $summary['pending_review_records'] = $this->workflowRecordQuery('pending_approval')
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn (CompiledDeviceRecord $record): array => [
                    ...$this->recordSummary($record),
                    'review_href' => route('admin.library.review', ['record' => $record->uuid ?: $record->id]),
                ])
                ->all();
            $summary['oem_submission_records'] = $this->workflowRecordQuery('pending_approval', 'oem')
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn (CompiledDeviceRecord $record): array => [
                    ...$this->recordSummary($record),
                    'review_href' => route('admin.library.review', ['record' => $record->uuid ?: $record->id]),
                    'source_label' => 'OEM',
                ])
                ->all();
            $summary['changes_requested_records'] = $this->workflowRecordQuery('changes_requested')
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn (CompiledDeviceRecord $record): array => [
                    ...$this->recordSummary($record),
                    'review_href' => route('admin.library.review', ['record' => $record->uuid ?: $record->id]),
                ])
                ->all();
            $summary['recent_records'] = $this->workflowRecordQuery('recently_published')
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn (CompiledDeviceRecord $record): array => [
                    ...$this->recordSummary($record),
                    'review_href' => route('admin.library.review', ['record' => $record->uuid ?: $record->id]),
                ])
                ->all();
        }

        if (Schema::hasTable('device_datasheets')) {
            $summary['partner_submissions'] = DeviceDatasheet::query()
                ->where('source_type', 'partner_submitted')
                ->count();
            $summary['failed_compiles'] = DeviceDatasheet::query()
                ->whereIn('status', ['failed', 'compile_failed', 'rejected'])
                ->count();
            $summary['failed_compile_datasheets'] = DeviceDatasheet::query()
                ->whereIn('status', ['failed', 'compile_failed'])
                ->latest()
                ->limit(3)
                ->get()
                ->map(fn (DeviceDatasheet $datasheet): array => [
                    'id' => $datasheet->id,
                    'uuid' => $datasheet->uuid,
                    'manufacturer' => $datasheet->manufacturer,
                    'product_name' => $datasheet->product_name,
                    'filename' => $datasheet->datasheet_original_filename,
                    'status' => $datasheet->status,
                    'created_at' => $datasheet->created_at?->toDateString(),
                ])
                ->all();
            $summary['activity']['oem_partner']['submissions'] = $summary['partner_submissions'];
        }

        if (Schema::hasTable('users')) {
            $partnerRoles = LineWattRole::partnerRoles();

            $summary['activity']['subscriber']['total'] = User::query()
                ->where('role', LineWattRole::SUBSCRIBER)
                ->count();
            $summary['activity']['subscriber']['added_this_week'] = User::query()
                ->where('role', LineWattRole::SUBSCRIBER)
                ->where('created_at', '>=', now()->subWeek())
                ->count();
            $summary['activity']['subscriber']['dropped_this_week'] = User::query()
                ->where('role', LineWattRole::SUBSCRIBER)
                ->whereIn('subscription_status', ['canceled', 'cancelled', 'expired', 'inactive'])
                ->where('updated_at', '>=', now()->subWeek())
                ->count();
            $summary['activity']['oem_partner']['total'] = User::query()
                ->whereIn('role', $partnerRoles)
                ->count();
            $summary['activity']['oem_partner']['added_this_week'] = User::query()
                ->whereIn('role', $partnerRoles)
                ->where('created_at', '>=', now()->subWeek())
                ->count();
            $summary['activity']['subscribers_added'] = User::query()
                ->where('role', LineWattRole::SUBSCRIBER)
                ->where('created_at', '>=', now()->subDays(30))
                ->count();
        }

        return Inertia::render('LineWatt/CentralLibrary', [
            'workspace' => [
                'name' => 'Library Admin',
                'role' => auth()->user()?->role,
                'role_label' => LineWattRole::label(auth()->user()?->role),
            ],
            'summary' => $summary,
        ]);
    }

    public function approvalQueue(Request $request): Response
    {
        return $this->workflowList(
            $request,
            'approval_queue',
            'Review & Approval',
            'Structured Engineering Data workflow actions, approvals, requested changes, rejected items and recently published records.',
            'pending_approval'
        );
    }

    public function engineeringData(Request $request): Response
    {
        return $this->workflowList(
            $request,
            'engineering_data',
            'Engineering Data Operations',
            'Model-level structured engineering records derived from datasheets.',
            'pending_approval'
        );
    }

    public function review(Request $request): RedirectResponse
    {
        return redirect()->route('admin.library.engineering-data', ['view' => 'validation_warnings']);
    }

    public function pendingApproval(Request $request): RedirectResponse
    {
        return redirect()->route('admin.library.approval-queue', [
            'view' => 'pending_approval',
            'source' => $request->query('source'),
        ]);
    }

    public function changesRequested(Request $request): RedirectResponse
    {
        return redirect()->route('admin.library.approval-queue', ['view' => 'changes_requested']);
    }

    public function published(Request $request): RedirectResponse
    {
        return redirect()->route('admin.library.engineering-data', ['view' => 'published']);
    }

    public function manufacturers(Request $request): Response
    {
        $tab = in_array($request->string('tab')->toString(), ['all', 'modules', 'inverters'], true)
            ? $request->string('tab')->toString()
            : 'all';
        $letter = $this->normalizedLetter($request->query('letter'));
        $search = trim($request->string('q')->toString());
        $letter = $search !== '' ? null : $letter;

        $hasListingFilter = $letter !== null || $search !== '';
        $manufacturers = $hasListingFilter
            ? $this->manufacturerNamesQuery($tab, $letter, $search)
                ->paginate(20)
                ->withQueryString()
                ->through(fn (object $row): array => $this->manufacturerDirectoryRow((string) $row->name))
                ->toArray()
            : (new LengthAwarePaginator([], 0, 20, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]))->toArray();

        return Inertia::render('LineWatt/LibraryAdminManufacturers', [
            'roleLabel' => LineWattRole::label(auth()->user()?->role),
            'stats' => $this->manufacturerDirectoryStats(),
            'manufacturers' => $manufacturers,
            'filters' => [
                'tab' => $tab,
                'letter' => $letter,
                'q' => $search,
            ],
            'hasListingFilter' => $hasListingFilter,
            'alphabet' => range('A', 'Z'),
        ]);
    }

    public function manufacturerSearch(Request $request): JsonResponse
    {
        $query = trim($request->string('q')->toString());

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $tab = in_array($request->string('tab')->toString(), ['all', 'modules', 'inverters'], true)
            ? $request->string('tab')->toString()
            : 'all';

        return response()->json(
            $this->manufacturerNamesQuery($tab, null, $query)
                ->limit(20)
                ->get()
                ->map(fn (object $row): array => [
                    'label' => (string) $row->name,
                    'value' => (string) $row->name,
                    'url' => route('admin.library.manufacturers', ['tab' => $tab, 'q' => (string) $row->name]),
                ])
                ->all()
        );
    }

    public function manufacturerInventory(Request $request, string $manufacturer): Response
    {
        $name = $this->manufacturerNameFromSlug($manufacturer);
        abort_unless($name, 404);

        $company = ManufacturerCompany::query()
            ->where('slug', ManufacturerCompany::slugFor($name))
            ->orWhere('name', $name)
            ->first();

        $datasheets = DeviceDatasheet::query()
            ->where('manufacturer', $name)
            ->latest()
            ->paginate(10, ['*'], 'datasheets_page')
            ->withQueryString()
            ->through(fn (DeviceDatasheet $datasheet): array => [
                'id' => $datasheet->id,
                'uuid' => $datasheet->uuid,
                'title' => $datasheet->product_name ?: $datasheet->series ?: $datasheet->datasheet_original_filename ?: 'Untitled datasheet',
                'device_type' => $datasheet->device_type,
                'status' => $datasheet->status,
                'review_status' => $datasheet->review_status,
                'uploaded_at' => $datasheet->created_at?->toDateString(),
                'href' => route('admin.library.datasheets.review', ['datasheet' => $datasheet->id]),
            ])
            ->toArray();

        $records = CompiledDeviceRecord::query()
            ->where('manufacturer', $name)
            ->latest()
            ->paginate(10, ['*'], 'records_page')
            ->withQueryString()
            ->through(fn (CompiledDeviceRecord $record): array => [
                ...$this->recordSummary($record),
                'href' => route('admin.library.review', ['record' => $record->uuid ?: $record->id]),
            ])
            ->toArray();

        return Inertia::render('LineWatt/LibraryAdminManufacturerInventory', [
            'roleLabel' => LineWattRole::label(auth()->user()?->role),
            'manufacturer' => [
                ...$this->manufacturerDirectoryRow($name),
                'is_subscribed' => $company instanceof ManufacturerCompany,
                'create_oem_href' => route('admin.library.oem-subscribers.new', ['manufacturer' => $name]),
            ],
            'datasheets' => $datasheets,
            'records' => $records,
        ]);
    }

    public function oems(Request $request): Response
    {
        $companies = [
            'data' => [],
            'links' => [],
            'current_page' => 1,
            'last_page' => 1,
            'from' => null,
            'to' => null,
            'total' => 0,
        ];

        if (Schema::hasTable('manufacturer_companies')) {
            $companies = ManufacturerCompany::query()
                ->withCount('users')
                ->latest()
                ->paginate(15)
                ->withQueryString()
                ->through(fn (ManufacturerCompany $company): array => $this->oemSummary($company))
                ->toArray();
        }

        return Inertia::render('LineWatt/LibraryAdminOems', [
            'roleLabel' => LineWattRole::label(auth()->user()?->role),
            'companies' => $companies,
        ]);
    }

    public function oem(ManufacturerCompany $oem): Response
    {
        $recordsQuery = CompiledDeviceRecord::query()
            ->where('source_type', 'partner_submitted')
            ->where('manufacturer', $oem->name);

        $datasheets = DeviceDatasheet::query()
            ->where('source_type', 'partner_submitted')
            ->where(function ($query) use ($oem): void {
                $query
                    ->where('manufacturer', $oem->name)
                    ->orWhere('metadata->manufacturer_company_id', $oem->id);
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (DeviceDatasheet $datasheet): array => [
                'id' => $datasheet->id,
                'uuid' => $datasheet->uuid,
                'filename' => $datasheet->datasheet_original_filename,
                'product_name' => $datasheet->product_name,
                'device_type' => $datasheet->device_type,
                'status' => $datasheet->status,
                'created_at' => $datasheet->created_at?->toDateString(),
            ])
            ->all();

        $records = (clone $recordsQuery)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (CompiledDeviceRecord $record): array => [
                ...$this->recordSummary($record),
                'review_href' => route('admin.library.review', ['record' => $record->uuid ?: $record->id]),
            ])
            ->all();

        return Inertia::render('LineWatt/LibraryAdminOemDetail', [
            'roleLabel' => LineWattRole::label(auth()->user()?->role),
            'company' => [
                ...$this->oemSummary($oem),
                'users' => $oem->users()
                    ->latest()
                    ->limit(8)
                    ->get()
                    ->map(fn (User $user): array => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => LineWattRole::label($user->role),
                        'status' => $user->subscription_status ?? 'active',
                    ])
                    ->all(),
                'factory_count' => $oem->factoryLocations()->count(),
                'distribution_count' => $oem->distributionCountries()->count(),
                'supporting_documents_count' => $oem->supportingDocuments()->count(),
                'datasheets' => $datasheets,
                'records' => $records,
                'submissions_count' => (clone $recordsQuery)
                    ->where(function ($query): void {
                        $query->where('status', 'submitted_for_approval')->orWhere('review_status', 'submitted');
                    })
                    ->count(),
            ],
        ]);
    }

    public function allDatasheets(Request $request): Response
    {
        $supportedViews = [
            'all',
            'new_uploads',
            'pending_review',
            'submitted_for_approval',
            'failed_compiles',
            'duplicate_review',
            'changes_requested',
            'published',
            'archived',
            'rejected',
        ];
        $view = $request->string('view')->snake()->toString() ?: 'new_uploads';
        $view = in_array($view, $supportedViews, true) ? $view : 'new_uploads';

        $filters = [
            'keyword' => trim($request->string('keyword')->toString()),
            'manufacturer' => trim($request->string('manufacturer')->toString()),
            'device_type' => in_array($request->string('device_type')->toString(), ['module', 'inverter'], true)
                ? $request->string('device_type')->toString()
                : 'module',
            'status' => $request->string('status')->toString(),
            'review_status' => $request->string('review_status')->toString(),
            'source_type' => $request->string('source_type')->toString(),
            'pdf_access_mode' => $request->string('pdf_access_mode')->toString(),
            'uploaded_from' => $request->string('uploaded_from')->toString(),
            'uploaded_to' => $request->string('uploaded_to')->toString(),
        ];

        $summary = [
            'all' => 0,
            'new_uploads' => 0,
            'pending_review' => 0,
            'failed_compiles' => 0,
            'duplicate_candidates' => 0,
        ];
        $filterOptions = [
            'statuses' => [],
            'review_statuses' => [],
            'source_types' => [],
            'pdf_access_modes' => [],
        ];
        $datasheets = [
            'data' => [],
            'current_page' => 1,
            'from' => null,
            'last_page' => 1,
            'per_page' => 20,
            'to' => null,
            'total' => 0,
            'links' => [],
        ];

        if (Schema::hasTable('device_datasheets')) {
            $base = DeviceDatasheet::query()->whereIn('device_type', ['module', 'inverter']);
            $duplicateIds = $this->duplicateDatasheetIds();

            $summary = [
                'all' => (clone $base)->count(),
                'new_uploads' => $this->applyDatasheetView((clone $base), 'new_uploads', $duplicateIds)->count(),
                'pending_review' => $this->applyDatasheetView((clone $base), 'pending_review', $duplicateIds)->count(),
                'failed_compiles' => $this->applyDatasheetView((clone $base), 'failed_compiles', $duplicateIds)->count(),
                'duplicate_candidates' => count($duplicateIds),
            ];

            $filterOptions = [
                'statuses' => (clone $base)->whereNotNull('status')->distinct()->orderBy('status')->pluck('status')->values()->all(),
                'review_statuses' => (clone $base)->whereNotNull('review_status')->distinct()->orderBy('review_status')->pluck('review_status')->values()->all(),
                'source_types' => (clone $base)->whereNotNull('source_type')->distinct()->orderBy('source_type')->pluck('source_type')->values()->all(),
                'pdf_access_modes' => (clone $base)->whereNotNull('pdf_access_mode')->distinct()->orderBy('pdf_access_mode')->pluck('pdf_access_mode')->values()->all(),
            ];

            $query = $this->applyDatasheetView(
                (clone $base)->withCount('compiledRecords'),
                $view,
                $duplicateIds
            );
            $this->applyDatasheetFilters($query, $filters);

            $datasheets = $query
                ->orderByRaw("lower(coalesce(manufacturer, '')) asc")
                ->orderByDesc('updated_at')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (DeviceDatasheet $datasheet): array => $this->datasheetOperationRow($datasheet, $duplicateIds))
                ->toArray();
        }

        return Inertia::render('LineWatt/LibraryAdminDatasheets', [
            'roleLabel' => LineWattRole::label(auth()->user()?->role),
            'view' => $view,
            'filters' => $filters,
            'summary' => $summary,
            'filterOptions' => $filterOptions,
            'views' => $this->datasheetSavedViews(),
            'datasheets' => $datasheets,
        ]);
    }

    /**
     * @return array<int,array{key:string,label:string,href:string}>
     */
    private function datasheetSavedViews(): array
    {
        return collect([
            ['key' => 'all', 'label' => 'All'],
            ['key' => 'new_uploads', 'label' => 'New Uploads'],
            ['key' => 'pending_review', 'label' => 'Pending Review'],
            ['key' => 'submitted_for_approval', 'label' => 'Submitted'],
            ['key' => 'failed_compiles', 'label' => 'Failed'],
            ['key' => 'duplicate_review', 'label' => 'Duplicates'],
            ['key' => 'changes_requested', 'label' => 'Changes Requested'],
            ['key' => 'published', 'label' => 'Published'],
            ['key' => 'archived', 'label' => 'Archived'],
            ['key' => 'rejected', 'label' => 'Rejected'],
        ])
            ->map(fn (array $view): array => [
                ...$view,
                'href' => route('admin.library.datasheets.all', ['view' => $view['key']]),
            ])
            ->all();
    }

    /**
     * @param  array<int,int>  $duplicateIds
     */
    private function applyDatasheetView(Builder $query, string $view, array $duplicateIds): Builder
    {
        return match ($view) {
            'new_uploads' => $query->whereIn('status', ['uploaded', 'security_checked', 'compiled', 'review_required'])
                ->where(function (Builder $nested): void {
                    $nested->whereNull('review_status')->orWhereIn('review_status', ['not_reviewed', 'pending_review']);
                }),
            'pending_review' => $query->where(function (Builder $nested): void {
                $nested
                    ->whereIn('status', ['review_required', 'librarian_review', 'submitted_for_approval'])
                    ->orWhereIn('review_status', ['submitted', 'pending_review', 'librarian_review']);
            }),
            'submitted_for_approval' => $query->where(function (Builder $nested): void {
                $nested->where('status', 'submitted_for_approval')->orWhere('review_status', 'submitted');
            }),
            'failed_compiles' => $query->whereIn('status', ['failed', 'compile_failed']),
            'duplicate_review' => empty($duplicateIds) ? $query->whereRaw('1 = 0') : $query->whereIn('id', $duplicateIds),
            'changes_requested' => $query->where(function (Builder $nested): void {
                $nested->where('status', 'changes_requested')->orWhere('review_status', 'changes_requested');
            }),
            'published' => $query->where('status', 'published'),
            'archived' => $query->whereIn('status', ['archived', 'discontinued', 'replaced']),
            'rejected' => $query->where('status', 'rejected'),
            default => $query,
        };
    }

    /**
     * @param  array<string,string>  $filters
     */
    private function applyDatasheetFilters(Builder $query, array $filters): void
    {
        if ($filters['keyword'] !== '') {
            $keyword = '%'.strtolower($filters['keyword']).'%';
            $query->where(function (Builder $nested) use ($keyword): void {
                $nested
                    ->whereRaw('lower(coalesce(product_name, \'\')) like ?', [$keyword])
                    ->orWhereRaw('lower(coalesce(series, \'\')) like ?', [$keyword])
                    ->orWhereRaw('lower(coalesce(manufacturer, \'\')) like ?', [$keyword])
                    ->orWhereRaw('lower(coalesce(datasheet_original_filename, \'\')) like ?', [$keyword]);
            });
        }

        foreach (['manufacturer', 'device_type', 'status', 'review_status', 'source_type', 'pdf_access_mode'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $query->where($field, $filters[$field]);
            }
        }

        if ($this->looksLikeDate($filters['uploaded_from'])) {
            $query->whereDate('created_at', '>=', $filters['uploaded_from']);
        }

        if ($this->looksLikeDate($filters['uploaded_to'])) {
            $query->whereDate('created_at', '<=', $filters['uploaded_to']);
        }
    }

    private function looksLikeDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }

    /**
     * @return array<int,int>
     */
    private function duplicateDatasheetIds(): array
    {
        if (! Schema::hasTable('device_datasheets')) {
            return [];
        }

        $ids = collect();

        DeviceDatasheet::query()
            ->whereNotNull('datasheet_sha256')
            ->where('datasheet_sha256', '<>', '')
            ->select('datasheet_sha256')
            ->groupBy('datasheet_sha256')
            ->havingRaw('count(*) > 1')
            ->pluck('datasheet_sha256')
            ->each(function (string $sha) use ($ids): void {
                DeviceDatasheet::query()
                    ->where('datasheet_sha256', $sha)
                    ->pluck('id')
                    ->each(fn (int $id) => $ids->push($id));
            });

        DeviceDatasheet::query()
            ->whereNotNull('manufacturer')
            ->where(function (Builder $query): void {
                $query->whereNotNull('series')->orWhereNotNull('product_name');
            })
            ->get(['id', 'manufacturer', 'series', 'product_name', 'metadata'])
            ->groupBy(fn (DeviceDatasheet $datasheet): string => strtolower(implode('|', [
                trim((string) $datasheet->manufacturer),
                trim((string) ($datasheet->series ?: $datasheet->product_name)),
                trim((string) ($datasheet->metadata['revision'] ?? $datasheet->metadata['datasheet_revision'] ?? '')),
            ])))
            ->filter(fn ($group, string $key): bool => $key !== '||' && $group->count() > 1)
            ->each(fn ($group) => $group->pluck('id')->each(fn (int $id) => $ids->push($id)));

        DeviceDatasheet::query()
            ->whereNotNull('manufacturer')
            ->whereNotNull('datasheet_original_filename')
            ->get(['id', 'manufacturer', 'datasheet_original_filename'])
            ->groupBy(fn (DeviceDatasheet $datasheet): string => strtolower(trim((string) $datasheet->manufacturer)).'|'.strtolower(trim((string) $datasheet->datasheet_original_filename)))
            ->filter(fn ($group, string $key): bool => $key !== '|' && $group->count() > 1)
            ->each(fn ($group) => $group->pluck('id')->each(fn (int $id) => $ids->push($id)));

        return $ids->unique()->values()->all();
    }

    /**
     * @param  array<int,int>  $duplicateIds
     * @return array<string,mixed>
     */
    private function datasheetOperationRow(DeviceDatasheet $datasheet, array $duplicateIds): array
    {
        $metadata = $datasheet->metadata ?? [];
        $uploadedBy = $metadata['uploaded_by'] ?? null;
        $uploadedByLabel = $uploadedBy
            ? (User::query()->whereKey($uploadedBy)->value('email') ?: '#'.$uploadedBy)
            : null;
        $compileStatus = $metadata['compile_status']
            ?? $metadata['compiler_status']
            ?? $metadata['compile_error']
            ?? $metadata['compiler_error']
            ?? $datasheet->compiler_version
            ?? null;

        return [
            'id' => $datasheet->id,
            'uuid' => $datasheet->uuid,
            'title' => $datasheet->product_name ?: $datasheet->series ?: $datasheet->datasheet_original_filename ?: 'Untitled datasheet',
            'manufacturer' => $datasheet->manufacturer ?: 'Unassigned supplier',
            'device_type' => $datasheet->device_type,
            'family_series' => $datasheet->series ?: $datasheet->product_name,
            'models_count' => (int) ($datasheet->compiled_records_count ?? 0),
            'status' => $datasheet->status,
            'review_status' => $datasheet->review_status,
            'compile_status' => is_string($compileStatus) ? $compileStatus : null,
            'pdf_access_mode' => $datasheet->pdf_access_mode ?: 'internal_only',
            'source_type' => $datasheet->source_type,
            'uploaded_by' => $uploadedByLabel ?: '—',
            'uploaded_at' => $datasheet->created_at?->toDateTimeString(),
            'filename' => $datasheet->datasheet_original_filename ?: 'datasheet.pdf',
            'duplicate_candidate' => in_array((int) $datasheet->id, $duplicateIds, true),
            'actions' => [
                'preview_pdf' => route('admin.library.datasheets.review.source-pdf', ['datasheet' => $datasheet->id]),
                'review_compilation' => route('admin.library.datasheets.review', ['datasheet' => $datasheet->id]),
                'retry_compile' => null,
                'duplicate_review' => route('admin.library.datasheets.all', ['view' => 'duplicate_review']),
                'replace_datasheet' => null,
                'history' => null,
                'approve_publish' => route('admin.library.datasheets.review.approve', ['datasheet' => $datasheet->id]),
                'request_changes' => route('admin.library.datasheets.review.changes-requested', ['datasheet' => $datasheet->id]),
                'reject' => route('admin.library.datasheets.review.reject', ['datasheet' => $datasheet->id]),
            ],
        ];
    }

    public function placeholder(Request $request, string $section, ?string $page = null): Response
    {
        $title = collect([$section, $page])
            ->filter()
            ->map(fn (string $part): string => str($part)->replace('-', ' ')->title()->toString())
            ->implode(' · ');

        return Inertia::render('LineWatt/LibraryAdminPlaceholder', [
            'roleLabel' => LineWattRole::label(auth()->user()?->role),
            'section' => str($section)->replace('-', ' ')->title()->toString(),
            'title' => $title ?: 'Library Operations',
            'description' => $this->placeholderDescription($section, $page),
            'items' => $this->placeholderItems($section, $page),
        ]);
    }

    private function workflowList(Request $request, string $pageType, string $title, string $description, string $defaultView): Response
    {
        $view = $request->string('view')->snake()->toString() ?: $defaultView;
        $allowedViews = $pageType === 'approval_queue'
            ? ['pending_approval', 'changes_requested', 'rejected', 'recently_published', 'all']
            : ['all', 'pending_approval', 'pending_review', 'validation_warnings', 'changes_requested', 'rejected', 'published', 'archived'];
        $view = in_array($view, $allowedViews, true) ? $view : $defaultView;
        $source = $request->string('source')->toString();
        $source = in_array($source, ['oem', 'publisher', 'librarian', 'central'], true) ? $source : null;
        $filters = $this->workflowFiltersFromRequest($request);
        if ($pageType === 'engineering_data' && ! in_array($filters['device_type'], ['module', 'inverter'], true)) {
            $filters['device_type'] = 'module';
        }
        $summary = [
            'all' => 0,
            'pending_approval' => 0,
            'pending_review' => 0,
            'validation_warnings' => 0,
            'changes_requested' => 0,
            'published' => 0,
        ];
        $filterOptions = [
            'device_types' => [],
            'statuses' => [],
            'review_statuses' => [],
            'validation_statuses' => [],
            'source_types' => [],
        ];
        $records = [
            'data' => [],
            'current_page' => 1,
            'from' => null,
            'last_page' => 1,
            'per_page' => 15,
            'to' => null,
            'total' => 0,
            'links' => [],
        ];

        if (Schema::hasTable('compiled_device_records')) {
            if ($pageType === 'engineering_data') {
                $summary = $this->workflowSummary($filters['device_type']);
                $filterOptions = $this->workflowFilterOptions();
            }

            $query = $this->workflowRecordQuery($view, $source);
            if ($pageType === 'engineering_data') {
                $this->applyWorkflowFilters($query, $filters);
            }

            $records = $query
                ->latest()
                ->paginate(15)
                ->withQueryString()
                ->through(fn (CompiledDeviceRecord $record): array => $this->workflowRecordRow($record))
                ->toArray();
        }

        return Inertia::render('LineWatt/WorkflowRecordList', [
            'workspaceTitle' => 'Library Admin',
            'pageType' => $pageType,
            'view' => $view,
            'source' => $source,
            'title' => $title,
            'description' => $description,
            'records' => $records,
            'views' => $this->workflowViews($pageType, $pageType === 'engineering_data' ? $filters['device_type'] : null),
            'summary' => $summary,
            'filters' => $filters,
            'filterOptions' => $filterOptions,
        ]);
    }

    private function workflowRecordQuery(string $view = 'all', ?string $source = null): Builder
    {
        $query = CompiledDeviceRecord::query()
            ->with('datasheet')
            ->whereIn('source_type', ['central_curated', 'partner_submitted']);

        $this->applyWorkflowSource($query, $source);

        match ($view) {
            'pending_approval' => $query->where(function (Builder $nested): void {
                $nested
                    ->whereIn('status', ['submitted_for_approval', 'librarian_review'])
                    ->orWhereIn('review_status', ['submitted', 'librarian_review']);
            }),
            'pending_review' => $query
                ->whereIn('status', ['compiled', 'review_required', 'publisher_review'])
                ->where(function (Builder $nested): void {
                    $nested->whereNull('review_status')->orWhereIn('review_status', ['not_reviewed', 'publisher_reviewed', 'pending_review']);
                }),
            'changes_requested' => $query->where(function (Builder $nested): void {
                $nested->where('status', 'changes_requested')->orWhere('review_status', 'changes_requested');
            }),
            'rejected' => $query->where(function (Builder $nested): void {
                $nested->where('status', 'rejected')->orWhere('review_status', 'rejected');
            }),
            'published' => $query->where('status', 'published'),
            'recently_published' => $query->where('status', 'published')->where('updated_at', '>=', now()->subDays(30)),
            'validation_warnings' => $query->where('validation_status', 'warnings'),
            'archived' => $query->whereIn('status', ['archived', 'discontinued', 'replaced']),
            default => $query,
        };

        return $query;
    }

    /**
     * @return array<string,string>
     */
    private function workflowFiltersFromRequest(Request $request): array
    {
        return [
            'keyword' => trim($request->string('keyword')->toString()),
            'manufacturer' => trim($request->string('manufacturer')->toString()),
            'device_type' => $request->string('device_type')->toString(),
            'status' => $request->string('status')->toString(),
            'review_status' => $request->string('review_status')->toString(),
            'validation_status' => $request->string('validation_status')->toString(),
            'source_type' => $request->string('source_type')->toString(),
            'updated_from' => $request->string('updated_from')->toString(),
            'updated_to' => $request->string('updated_to')->toString(),
        ];
    }

    /**
     * @param  array<string,string>  $filters
     */
    private function applyWorkflowFilters(Builder $query, array $filters): void
    {
        if ($filters['keyword'] !== '') {
            $keyword = '%'.mb_strtolower($filters['keyword']).'%';
            $query->where(function (Builder $nested) use ($keyword): void {
                $nested
                    ->whereRaw('lower(coalesce(manufacturer, \'\')) like ?', [$keyword])
                    ->orWhereRaw('lower(coalesce(series, \'\')) like ?', [$keyword])
                    ->orWhereRaw('lower(coalesce(family, \'\')) like ?', [$keyword])
                    ->orWhereRaw('lower(coalesce(technology, \'\')) like ?', [$keyword])
                    ->orWhereRaw('lower(coalesce(model_series, \'\')) like ?', [$keyword])
                    ->orWhereRaw('lower(coalesce(model_name, \'\')) like ?', [$keyword])
                    ->orWhereRaw('lower(coalesce(display_name, \'\')) like ?', [$keyword]);
            });
        }

        if ($filters['manufacturer'] !== '') {
            $query->whereRaw('lower(manufacturer) like ?', ['%'.mb_strtolower($filters['manufacturer']).'%']);
        }

        foreach (['device_type', 'status', 'review_status', 'validation_status', 'source_type'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $query->where($field, $filters[$field]);
            }
        }

        if ($this->looksLikeDate($filters['updated_from'])) {
            $query->whereDate('updated_at', '>=', $filters['updated_from']);
        }

        if ($this->looksLikeDate($filters['updated_to'])) {
            $query->whereDate('updated_at', '<=', $filters['updated_to']);
        }
    }

    /**
     * @return array<string,int>
     */
    private function workflowSummary(?string $deviceType = null): array
    {
        $base = CompiledDeviceRecord::query()
            ->whereIn('source_type', ['central_curated', 'partner_submitted']);

        if (in_array($deviceType, ['module', 'inverter'], true)) {
            $base->where('device_type', $deviceType);
        }

        return [
            'all' => (clone $base)->count(),
            'pending_approval' => $this->workflowRecordQuery('pending_approval')->when(in_array($deviceType, ['module', 'inverter'], true), fn (Builder $query) => $query->where('device_type', $deviceType))->count(),
            'pending_review' => $this->workflowRecordQuery('pending_review')->when(in_array($deviceType, ['module', 'inverter'], true), fn (Builder $query) => $query->where('device_type', $deviceType))->count(),
            'validation_warnings' => $this->workflowRecordQuery('validation_warnings')->when(in_array($deviceType, ['module', 'inverter'], true), fn (Builder $query) => $query->where('device_type', $deviceType))->count(),
            'changes_requested' => $this->workflowRecordQuery('changes_requested')->when(in_array($deviceType, ['module', 'inverter'], true), fn (Builder $query) => $query->where('device_type', $deviceType))->count(),
            'published' => $this->workflowRecordQuery('published')->when(in_array($deviceType, ['module', 'inverter'], true), fn (Builder $query) => $query->where('device_type', $deviceType))->count(),
        ];
    }

    /**
     * @return array<string,list<string>>
     */
    private function workflowFilterOptions(): array
    {
        $base = CompiledDeviceRecord::query()
            ->whereIn('source_type', ['central_curated', 'partner_submitted']);

        return [
            'device_types' => (clone $base)->whereNotNull('device_type')->distinct()->orderBy('device_type')->pluck('device_type')->values()->all(),
            'statuses' => (clone $base)->whereNotNull('status')->distinct()->orderBy('status')->pluck('status')->values()->all(),
            'review_statuses' => (clone $base)->whereNotNull('review_status')->distinct()->orderBy('review_status')->pluck('review_status')->values()->all(),
            'validation_statuses' => (clone $base)->whereNotNull('validation_status')->distinct()->orderBy('validation_status')->pluck('validation_status')->values()->all(),
            'source_types' => (clone $base)->whereNotNull('source_type')->distinct()->orderBy('source_type')->pluck('source_type')->values()->all(),
        ];
    }

    private function applyWorkflowSource(Builder $query, ?string $source): void
    {
        match ($source) {
            'oem' => $query->where('source_type', 'partner_submitted'),
            'publisher' => $query->where('source_type', 'central_curated')->where('metadata->upload_workspace', 'publisher'),
            'librarian', 'central' => $query->where('source_type', 'central_curated')->where(function (Builder $nested): void {
                $nested->where('metadata->upload_workspace', 'central')->orWhereNull('metadata->upload_workspace');
            }),
            default => null,
        };
    }

    /**
     * @return list<array{key:string,label:string,href:string}>
     */
    private function workflowViews(string $pageType, ?string $deviceType = null): array
    {
        $route = $pageType === 'approval_queue' ? 'admin.library.approval-queue' : 'admin.library.engineering-data';
        $views = $pageType === 'approval_queue'
            ? [
                ['key' => 'pending_approval', 'label' => 'Pending Approval'],
                ['key' => 'changes_requested', 'label' => 'Changes Requested'],
                ['key' => 'rejected', 'label' => 'Rejected'],
                ['key' => 'recently_published', 'label' => 'Recently Published'],
                ['key' => 'all', 'label' => 'All Workflow Items'],
            ]
            : [
                ['key' => 'all', 'label' => 'All'],
                ['key' => 'pending_approval', 'label' => 'Pending Approval'],
                ['key' => 'pending_review', 'label' => 'Pending Review'],
                ['key' => 'validation_warnings', 'label' => 'Validation Warnings'],
                ['key' => 'changes_requested', 'label' => 'Changes Requested'],
                ['key' => 'rejected', 'label' => 'Rejected'],
                ['key' => 'published', 'label' => 'Published'],
                ['key' => 'archived', 'label' => 'Archived'],
            ];

        return collect($views)
            ->map(fn (array $view): array => [
                ...$view,
                'href' => route($route, array_filter([
                    'view' => $view['key'],
                    'device_type' => in_array($deviceType, ['module', 'inverter'], true) ? $deviceType : null,
                ])),
            ])
            ->all();
    }

    private function workflowRecordRow(CompiledDeviceRecord $record): array
    {
        $summary = $this->recordSummary($record);
        $datasheet = $record->datasheet;
        $submittedBy = $record->metadata['submitted_by'] ?? $record->metadata['uploaded_by'] ?? $datasheet?->metadata['uploaded_by'] ?? null;
        $submittedAt = $record->metadata['submitted_at'] ?? $record->updated_at?->toIso8601String();
        $reviewHref = route('admin.library.review', ['record' => $record->id]);

        return [
            ...$summary,
            'href' => $reviewHref,
            'review_href' => $reviewHref,
            'source_label' => $this->workflowSourceLabel($record),
            'datasheet_title' => $datasheet?->product_name ?: $datasheet?->series ?: $datasheet?->datasheet_original_filename ?: 'Datasheet pending',
            'datasheet_review_href' => $datasheet ? route('admin.library.datasheets.review', ['datasheet' => $datasheet->id]) : null,
            'model_review_href' => $reviewHref,
            'open_href' => $reviewHref,
            'submitted_by_label' => $submittedBy ? (User::query()->whereKey($submittedBy)->value('email') ?: '#'.$submittedBy) : '—',
            'submitted_at' => $submittedAt,
            'updated_at' => $record->updated_at?->toIso8601String(),
            'warning_count' => (int) ($record->metadata['warning_count'] ?? $record->metadata['validation_warning_count'] ?? ($record->validation_status === 'warnings' ? 1 : 0)),
            'history_href' => null,
        ];
    }

    private function workflowSourceLabel(CompiledDeviceRecord $record): string
    {
        if ($record->source_type === 'partner_submitted') {
            return 'OEM';
        }

        return match ($record->metadata['upload_workspace'] ?? null) {
            'publisher' => 'Publisher',
            'central' => 'Librarian',
            default => 'Central',
        };
    }

    private function oemSummary(ManufacturerCompany $company): array
    {
        $recordBase = CompiledDeviceRecord::query()->where('manufacturer', $company->name);
        $datasheetBase = DeviceDatasheet::query()->where('manufacturer', $company->name);
        $lastActivity = (clone $recordBase)->latest('updated_at')->value('updated_at')
            ?? (clone $datasheetBase)->latest('updated_at')->value('updated_at')
            ?? $company->updated_at;

        return [
            'id' => $company->id,
            'uuid' => $company->uuid,
            'name' => $company->name,
            'slug' => $company->slug,
            'plan' => $company->plan_label,
            'plan_code' => $company->plan_code,
            'status' => $company->subscription_status,
            'datasheets' => (clone $datasheetBase)->count(),
            'records' => (clone $recordBase)->count(),
            'pending_submissions' => (clone $recordBase)
                ->where(function ($query): void {
                    $query->where('status', 'submitted_for_approval')->orWhere('review_status', 'submitted');
                })
                ->count(),
            'last_activity' => $lastActivity ? \Illuminate\Support\Carbon::parse($lastActivity)->toDateString() : null,
            'href' => route('admin.library.oems.show', ['oem' => $company]),
        ];
    }

    private function manufacturerDirectoryStats(): array
    {
        return [
            'total' => (int) $this->manufacturerNamesQuery('all', null, '')->count(),
            'added_this_month' => $this->manufacturersAddedThisMonth(),
            'dropped_this_month' => Schema::hasTable('manufacturer_companies')
                ? ManufacturerCompany::query()
                    ->whereIn('subscription_status', ['inactive', 'archived', 'removed', 'suspended', 'cancelled', 'canceled', 'expired'])
                    ->where('updated_at', '>=', now()->startOfMonth())
                    ->count()
                : 0,
            'subscribed' => Schema::hasTable('manufacturer_companies') ? ManufacturerCompany::query()->count() : 0,
        ];
    }

    private function manufacturersAddedThisMonth(): int
    {
        $names = collect();

        if (Schema::hasTable('manufacturer_companies')) {
            $names = $names->merge(
                ManufacturerCompany::query()
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->pluck('name')
            );
        }

        if (Schema::hasTable('device_datasheets')) {
            $names = $names->merge(
                DeviceDatasheet::query()
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->whereNotNull('manufacturer')
                    ->where('manufacturer', '<>', '')
                    ->pluck('manufacturer')
            );
        }

        if (Schema::hasTable('compiled_device_records')) {
            $names = $names->merge(
                CompiledDeviceRecord::query()
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->whereNotNull('manufacturer')
                    ->where('manufacturer', '<>', '')
                    ->pluck('manufacturer')
            );
        }

        return $names
            ->filter()
            ->unique(fn (string $name): string => ManufacturerCompany::slugFor($name))
            ->count();
    }

    private function manufacturerDirectoryRow(string $name): array
    {
        $company = ManufacturerCompany::query()
            ->where('slug', ManufacturerCompany::slugFor($name))
            ->orWhere('name', $name)
            ->first();
        $datasheetBase = DeviceDatasheet::query()->where('manufacturer', $name);
        $recordBase = CompiledDeviceRecord::query()->where('manufacturer', $name);
        $deviceTypes = collect()
            ->merge((clone $datasheetBase)->whereNotNull('device_type')->distinct()->pluck('device_type'))
            ->merge((clone $recordBase)->whereNotNull('device_type')->distinct()->pluck('device_type'))
            ->unique()
            ->values()
            ->all();
        $lastUpdated = collect([
            $company?->updated_at,
            (clone $datasheetBase)->latest('updated_at')->value('updated_at'),
            (clone $recordBase)->latest('updated_at')->value('updated_at'),
        ])->filter()->sortDesc()->first();
        $metadata = $company?->metadata ?? [];

        return [
            'name' => $name,
            'slug' => ManufacturerCompany::slugFor($name),
            'headquarters_country' => $metadata['country'] ?? $metadata['headquarters_country'] ?? '—',
            'device_types' => $deviceTypes,
            'datasheets' => (clone $datasheetBase)->count(),
            'records' => (clone $recordBase)->count(),
            'subscriber_status' => $company?->subscription_status ?? 'not_subscribed',
            'status' => $this->manufacturerPresenceStatus($company, (clone $datasheetBase)->count(), (clone $recordBase)->count()),
            'last_updated' => $lastUpdated ? \Illuminate\Support\Carbon::parse($lastUpdated)->toDateString() : '—',
            'is_subscribed' => $company instanceof ManufacturerCompany,
            'href' => $company instanceof ManufacturerCompany
                ? route('admin.library.oem-subscribers.show', ['subscriber' => $company])
                : route('admin.library.manufacturers.inventory', ['manufacturer' => ManufacturerCompany::slugFor($name)]),
        ];
    }

    private function manufacturerPresenceStatus(?ManufacturerCompany $company, int $datasheets, int $records): string
    {
        if ($company && in_array($company->subscription_status, ['archived', 'removed', 'inactive', 'suspended'], true)) {
            return $company->subscription_status;
        }

        return $datasheets > 0 || $records > 0 || $company ? 'active' : 'inactive';
    }

    private function manufacturerNamesQuery(string $tab, ?string $letter, string $search): \Illuminate\Database\Query\Builder
    {
        $queries = collect();
        $deviceType = match ($tab) {
            'modules' => 'module',
            'inverters' => 'inverter',
            default => null,
        };

        if (Schema::hasTable('manufacturer_companies')) {
            $companyQuery = DB::table('manufacturer_companies')->selectRaw('name as name');

            if ($deviceType) {
                $companyQuery->where(function ($query) use ($deviceType): void {
                    if (Schema::hasTable('device_datasheets')) {
                        $query->whereExists(function ($exists) use ($deviceType): void {
                            $exists->selectRaw('1')
                                ->from('device_datasheets')
                                ->whereColumn('device_datasheets.manufacturer', 'manufacturer_companies.name')
                                ->where('device_datasheets.device_type', $deviceType);
                        });
                    }

                    if (Schema::hasTable('compiled_device_records')) {
                        $query->orWhereExists(function ($exists) use ($deviceType): void {
                            $exists->selectRaw('1')
                                ->from('compiled_device_records')
                                ->whereColumn('compiled_device_records.manufacturer', 'manufacturer_companies.name')
                                ->where('compiled_device_records.device_type', $deviceType);
                        });
                    }
                });
            }

            if ($search !== '') {
                $needle = '%'.mb_strtolower($search).'%';
                $companyQuery->where(function ($query) use ($needle): void {
                    $query
                        ->whereRaw('lower(name) like ?', [$needle])
                        ->orWhereRaw('lower(coalesce(metadata::text, \'\')) like ?', [$needle]);
                });
            }

            $queries->push($companyQuery);
        }

        if (Schema::hasTable('device_datasheets')) {
            $datasheetQuery = DB::table('device_datasheets')
                ->selectRaw('manufacturer as name')
                ->whereNotNull('manufacturer')
                ->where('manufacturer', '<>', '');

            if ($deviceType) {
                $datasheetQuery->where('device_type', $deviceType);
            }

            if ($search !== '') {
                $datasheetQuery->whereRaw('lower(manufacturer) like ?', ['%'.mb_strtolower($search).'%']);
            }

            $queries->push($datasheetQuery);
        }

        if (Schema::hasTable('compiled_device_records')) {
            $recordQuery = DB::table('compiled_device_records')
                ->selectRaw('manufacturer as name')
                ->whereNotNull('manufacturer')
                ->where('manufacturer', '<>', '');

            if ($deviceType) {
                $recordQuery->where('device_type', $deviceType);
            }

            if ($search !== '') {
                $recordQuery->whereRaw('lower(manufacturer) like ?', ['%'.mb_strtolower($search).'%']);
            }

            $queries->push($recordQuery);
        }

        if ($queries->isEmpty()) {
            return DB::query()->fromRaw('(select null as name) as manufacturers')->whereRaw('1 = 0');
        }

        /** @var \Illuminate\Database\Query\Builder $union */
        $union = $queries->shift();
        $queries->each(fn ($query) => $union->unionAll($query));

        $outer = DB::query()
            ->fromSub($union, 'manufacturers')
            ->select('name')
            ->whereNotNull('name')
            ->where('name', '<>', '')
            ->groupBy('name')
            ->orderByRaw('lower(name) asc');

        if ($letter) {
            $outer->whereRaw('upper(left(name, 1)) = ?', [$letter]);
        }

        return $outer;
    }

    private function normalizedLetter(mixed $letter): ?string
    {
        $letter = strtoupper(substr((string) $letter, 0, 1));

        return preg_match('/^[A-Z]$/', $letter) ? $letter : null;
    }

    private function manufacturerNameFromSlug(string $slug): ?string
    {
        $company = ManufacturerCompany::query()->where('slug', $slug)->first();

        if ($company) {
            return $company->name;
        }

        $names = $this->manufacturerNamesQuery('all', null, '')
            ->get()
            ->pluck('name');

        return $names->first(fn (string $name): bool => ManufacturerCompany::slugFor($name) === $slug);
    }

    private function placeholderDescription(string $section, ?string $page): string
    {
        return match ("{$section}.{$page}") {
            'governance.manufacturer-normalization' => 'Resolve canonical manufacturer names, aliases and duplicate OEM identities before they affect search or ownership.',
            'governance.manufacturer-conflicts' => 'Review claimed manufacturer ownership conflicts before granting OEM identity control.',
            'governance.power-search-tags' => 'Curate Power Search tags and review OEM tag requests for engineering search quality.',
            'governance.objectionable-content' => 'Review misleading claims, unauthorized logos, wrong manufacturer associations and policy-sensitive content.',
            'governance.blocked-content' => 'Track blocked datasheets, fake documents, malware events and security restrictions.',
            'operations.compiler-health' => 'Monitor compiler failures, extraction coverage and records needing engineering attention.',
            'operations.storage-health' => 'Check object storage paths, missing artifacts and hash verification health.',
            'operations.malware-scan-logs' => 'Inspect malware scan outcomes and blocked upload evidence.',
            'operations.notification-delivery' => 'Review in-app and email delivery status for approval workflow notifications.',
            default => 'Operational workspace placeholder. This page is intentionally scoped so the admin console has a stable navigation home before deeper tooling is implemented.',
        };
    }

    /**
     * @return array<int,string>
     */
    private function placeholderItems(string $section, ?string $page): array
    {
        return match ("{$section}.{$page}") {
            'governance.objectionable-content' => [
                'Misleading claims requiring engineering review',
                'Unauthorized manufacturer logos or branding',
                'Wrong manufacturer association',
                'Duplicate or fake datasheets',
                'Policy or subsidy claims requiring proof',
            ],
            'operations.compiler-health' => [
                'Failed compiles by manufacturer',
                'Warnings by compiler version',
                'Low validation grades',
                'Recent extraction regressions',
            ],
            default => [
                'Queue summary',
                'Assigned owner',
                'Open items',
                'Recent activity',
            ],
        };
    }
}
