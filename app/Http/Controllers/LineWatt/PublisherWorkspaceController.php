<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LineWatt\Concerns\BuildsEngineeringRecordPayloads;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\ManufacturerCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class PublisherWorkspaceController extends Controller
{
    use BuildsEngineeringRecordPayloads;

    public function index(Request $request): Response
    {
        return $this->dashboard($request);
    }

    private function dashboard(Request $request): Response
    {
        $userId = $request->user()?->id;
        $datasheetStats = $this->emptyStats();
        $recordStats = $this->emptyStats();
        $manufacturerStats = [
            'discovered' => 0,
            'approved' => 0,
            'pending_review' => 0,
            'rejected_merged' => 0,
            'duplicate_warnings' => 0,
        ];
        $monthStats = [
            'datasheets_compiled' => 0,
            'engineering_records_created' => 0,
            'manufacturers_discovered' => 0,
            'rejected_needs_rework' => 0,
        ];
        $attention = [];
        $recentWork = [];

        if ($userId && Schema::hasTable('device_datasheets')) {
            $datasheets = $this->publisherDatasheetQuery($userId);

            $datasheetStats = [
                'compiled' => (clone $datasheets)->whereIn('status', ['compiled', 'publisher_review', 'review_required', 'submitted_for_approval', 'approved', 'published'])->count(),
                'reviewed' => (clone $datasheets)->where(function (Builder $query) use ($userId): void {
                    $query->where('reviewed_by', $userId)->orWhereIn('review_status', ['publisher_reviewed', 'submitted', 'approved']);
                })->count(),
                'approved' => (clone $datasheets)->where(function (Builder $query): void {
                    $query->whereIn('status', ['approved', 'published'])->orWhereIn('review_status', ['approved', 'librarian_reviewed']);
                })->count(),
                'rejected_rework' => (clone $datasheets)->where(function (Builder $query): void {
                    $query->whereIn('status', ['rejected', 'changes_requested'])->orWhereIn('review_status', ['rejected', 'changes_requested']);
                })->count(),
                'pending_review' => (clone $datasheets)->where(function (Builder $query): void {
                    $query->whereIn('status', ['uploaded', 'security_checked', 'compiled', 'publisher_review', 'review_required'])
                        ->orWhereNull('review_status')
                        ->orWhereIn('review_status', ['not_reviewed', 'pending_review', 'publisher_reviewed']);
                })->count(),
                'approval_rate' => 0,
            ];
            $datasheetStats['approval_rate'] = $this->approvalRate($datasheetStats['approved'], $datasheetStats['rejected_rework']);

            $monthStats['datasheets_compiled'] = (clone $datasheets)
                ->where('updated_at', '>=', now()->startOfMonth())
                ->whereIn('status', ['compiled', 'publisher_review', 'review_required', 'submitted_for_approval', 'approved', 'published'])
                ->count();
            $monthStats['manufacturers_discovered'] = (clone $datasheets)
                ->where('created_at', '>=', now()->startOfMonth())
                ->whereNotNull('manufacturer')
                ->where('manufacturer', '<>', '')
                ->distinct('manufacturer')
                ->count('manufacturer');

            $manufacturerStats = [
                'discovered' => (clone $datasheets)->whereNotNull('manufacturer')->where('manufacturer', '<>', '')->distinct('manufacturer')->count('manufacturer'),
                'approved' => (clone $datasheets)->whereIn('status', ['approved', 'published'])->whereNotNull('manufacturer')->where('manufacturer', '<>', '')->distinct('manufacturer')->count('manufacturer'),
                'pending_review' => (clone $datasheets)->whereIn('status', ['publisher_review', 'compiled', 'review_required', 'submitted_for_approval'])->whereNotNull('manufacturer')->where('manufacturer', '<>', '')->distinct('manufacturer')->count('manufacturer'),
                'rejected_merged' => (clone $datasheets)->whereIn('status', ['rejected', 'changes_requested'])->whereNotNull('manufacturer')->where('manufacturer', '<>', '')->distinct('manufacturer')->count('manufacturer'),
                'duplicate_warnings' => (clone $datasheets)->where('metadata->manufacturer_mismatch_detected', true)->count(),
            ];
        }

        if ($userId && Schema::hasTable('compiled_device_records')) {
            $records = $this->publisherRecordQuery($userId);

            $recordStats = [
                'created' => (clone $records)->count(),
                'reviewed' => (clone $records)->where(function (Builder $query) use ($userId): void {
                    $query->where('reviewed_by', $userId)->orWhereIn('review_status', ['publisher_reviewed', 'submitted', 'approved']);
                })->count(),
                'approved' => (clone $records)->where(function (Builder $query): void {
                    $query->whereIn('status', ['approved', 'published'])->orWhereIn('review_status', ['approved', 'librarian_reviewed']);
                })->count(),
                'rejected_rework' => (clone $records)->where(function (Builder $query): void {
                    $query->whereIn('status', ['rejected', 'changes_requested'])->orWhereIn('review_status', ['rejected', 'changes_requested']);
                })->count(),
                'pending_review' => (clone $records)->where(function (Builder $query): void {
                    $query->whereIn('status', ['compiled', 'publisher_review', 'review_required'])
                        ->orWhereNull('review_status')
                        ->orWhereIn('review_status', ['not_reviewed', 'pending_review', 'publisher_reviewed']);
                })->count(),
                'approval_rate' => 0,
            ];
            $recordStats['approval_rate'] = $this->approvalRate($recordStats['approved'], $recordStats['rejected_rework']);

            $monthStats['engineering_records_created'] = (clone $records)
                ->where('created_at', '>=', now()->startOfMonth())
                ->count();
            $monthStats['rejected_needs_rework'] = (clone $records)
                ->where('updated_at', '>=', now()->startOfMonth())
                ->where(function (Builder $query): void {
                    $query->whereIn('status', ['rejected', 'changes_requested'])->orWhereIn('review_status', ['rejected', 'changes_requested']);
                })
                ->count();

            $attention = (clone $records)
                ->where(function (Builder $query): void {
                    $query
                        ->whereIn('status', ['rejected', 'changes_requested'])
                        ->orWhereIn('review_status', ['rejected', 'changes_requested'])
                        ->orWhere('validation_status', 'errors')
                        ->orWhere('metadata->duplicate_flagged', true)
                        ->orWhere('metadata->duplicate_warning', true)
                        ->orWhereNull('device_datasheet_id')
                        ->orWhereNull('manufacturer')
                        ->orWhereNull('display_name');
                })
                ->latest('updated_at')
                ->limit(8)
                ->get()
                ->map(fn (CompiledDeviceRecord $record): array => $this->publisherRecordRow($record))
                ->all();

            $recentWork = (clone $records)
                ->latest('updated_at')
                ->limit(10)
                ->get()
                ->map(fn (CompiledDeviceRecord $record): array => $this->publisherRecordRow($record))
                ->all();
        }

        return Inertia::render('LineWatt/PublisherDashboard', [
            'roleLabel' => 'Library Publisher',
            'datasheetStats' => $datasheetStats,
            'recordStats' => $recordStats,
            'manufacturerStats' => $manufacturerStats,
            'monthStats' => $monthStats,
            'attention' => $attention,
            'recentWork' => $recentWork,
        ]);
    }

    public function uploads(Request $request): Response
    {
        return $this->datasheets($request);
    }

    public function review(Request $request): Response
    {
        return $this->list($request, 'review');
    }

    public function submitted(Request $request): Response
    {
        return $this->list($request, 'submitted');
    }

    public function changesRequested(Request $request): Response
    {
        return $this->list($request, 'changes-requested');
    }

    public function search(Request $request): Response
    {
        return $this->list($request, 'search');
    }

    public function oemSubscribers(Request $request): Response
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
                ->through(fn (ManufacturerCompany $company): array => $this->publisherSubscriberSummary($company))
                ->toArray();
        }

        return Inertia::render('LineWatt/OemSubscribers', [
            'roleLabel' => 'Library Publisher',
            'companies' => $companies,
            'statuses' => [],
            'readonly' => true,
        ]);
    }

    private function list(Request $request, string $section): Response
    {
        $view = $request->string('view')->snake()->toString() ?: 'pending_review';
        $view = in_array($view, ['pending_review', 'rejected', 'all'], true) ? $view : 'pending_review';
        $deviceType = $request->string('device_type')->toString();
        $deviceType = in_array($deviceType, ['module', 'inverter'], true) ? $deviceType : 'module';
        $manufacturer = trim($request->string('manufacturer')->toString());
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
            $builder = CompiledDeviceRecord::query()
                ->with('datasheet')
                ->where('source_type', 'central_curated')
                ->where('device_type', $deviceType);

            if ($manufacturer !== '') {
                $builder->whereRaw('lower(manufacturer) like ?', ['%'.mb_strtolower($manufacturer).'%']);
            }

            match ($view) {
                'rejected' => $builder->where(function ($query): void {
                    $query->where('status', 'rejected')->orWhere('review_status', 'rejected');
                }),
                'all' => $builder,
                default => $builder
                    ->whereIn('status', ['publisher_review', 'compiled', 'review_required', 'changes_requested'])
                    ->where(function ($query): void {
                        $query->whereNull('review_status')
                            ->orWhereIn('review_status', ['not_reviewed', 'publisher_reviewed', 'pending_review', 'changes_requested']);
                    }),
            };

            $records = $builder
                ->latest()
                ->paginate(15)
                ->withQueryString()
                ->through(fn (CompiledDeviceRecord $record): array => [
                    ...$this->recordSummary($record),
                    'review_href' => route('publisher.review.show', ['record' => $record->uuid ?: $record->id]),
                    'open_href' => route('publisher.review.show', ['record' => $record->uuid ?: $record->id]),
                    'href' => route('publisher.review.show', ['record' => $record->uuid ?: $record->id]),
                ])
                ->toArray();
        }

        $basePath = $section === 'uploads' ? '/publisher/uploads' : '/publisher/review';
        $viewHref = fn (string $targetView): string => $basePath.'?'.http_build_query(array_filter([
            'view' => $targetView,
            'device_type' => $deviceType,
            'manufacturer' => $manufacturer !== '' ? $manufacturer : null,
        ]));

        return Inertia::render('LineWatt/WorkflowRecordList', [
            'workspaceTitle' => 'Library Publisher',
            'pageType' => 'engineering_data',
            'view' => $view,
            'basePath' => $basePath,
            'showSummaryCards' => false,
            'showFilters' => false,
            'showManufacturerFilter' => true,
            'title' => match ($section) {
                'uploads' => 'Publisher Datasheets',
                'submitted' => 'Submitted for Approval',
                'changes-requested' => 'Changes Requested',
                'search' => 'Publisher Search',
                default => 'Publisher Review',
            },
            'description' => 'Upload, review and submit Engineering Records for Librarian approval. Publishing remains controlled by Librarians.',
            'listTitle' => $section === 'uploads' ? 'Datasheets' : 'Engineering Data',
            'listDescription' => $section === 'uploads'
                ? 'Publisher-uploaded source datasheets grouped by product type and review basket.'
                : 'Model-level Structured Engineering Data prepared by the Publisher before Librarian approval.',
            'records' => $records,
            'views' => [
                ['key' => 'pending_review', 'label' => 'Pending Review', 'href' => $viewHref('pending_review')],
                ['key' => 'rejected', 'label' => 'Rejected', 'href' => $viewHref('rejected')],
                ['key' => 'all', 'label' => 'Show All', 'href' => $viewHref('all')],
            ],
            'filters' => [
                'keyword' => '',
                'manufacturer' => $manufacturer,
                'device_type' => $deviceType,
                'status' => '',
                'review_status' => '',
                'validation_status' => '',
                'source_type' => '',
                'updated_from' => '',
                'updated_to' => '',
            ],
            'filterOptions' => [
                'device_types' => ['module', 'inverter'],
                'statuses' => [],
                'review_statuses' => [],
                'validation_statuses' => [],
                'source_types' => [],
            ],
        ]);
    }

    private function datasheets(Request $request): Response
    {
        $view = $request->string('view')->snake()->toString() ?: 'pending_review';
        $view = in_array($view, ['pending_review', 'rejected', 'all'], true) ? $view : 'pending_review';
        $deviceType = $request->string('device_type')->toString();
        $deviceType = in_array($deviceType, ['module', 'inverter'], true) ? $deviceType : 'module';
        $manufacturer = trim($request->string('manufacturer')->toString());
        $keyword = trim($request->string('keyword')->toString());

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
            $query = DeviceDatasheet::query()
                ->withCount('compiledRecords')
                ->where('source_type', 'central_curated')
                ->where('device_type', $deviceType);

            match ($view) {
                'rejected' => $query->where(function ($nested): void {
                    $nested->where('status', 'rejected')->orWhere('review_status', 'rejected');
                }),
                'all' => $query,
                default => $query
                    ->whereIn('status', ['uploaded', 'security_checked', 'compiled', 'review_required', 'changes_requested'])
                    ->where(function ($nested): void {
                        $nested->whereNull('review_status')
                            ->orWhereIn('review_status', ['not_reviewed', 'pending_review', 'publisher_reviewed', 'changes_requested']);
                    }),
            };

            if ($manufacturer !== '') {
                $query->whereRaw('lower(manufacturer) like ?', ['%'.mb_strtolower($manufacturer).'%']);
            }

            if ($keyword !== '') {
                $needle = '%'.mb_strtolower($keyword).'%';
                $query->where(function ($nested) use ($needle): void {
                    $nested
                        ->whereRaw('lower(coalesce(product_name, \'\')) like ?', [$needle])
                        ->orWhereRaw('lower(coalesce(series, \'\')) like ?', [$needle])
                        ->orWhereRaw('lower(coalesce(datasheet_original_filename, \'\')) like ?', [$needle]);
                });
            }

            $datasheets = $query
                ->orderByRaw("lower(coalesce(manufacturer, '')) asc")
                ->orderByDesc('updated_at')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (DeviceDatasheet $datasheet): array => $this->publisherDatasheetRow($datasheet))
                ->toArray();
        }

        $viewHref = fn (string $targetView): string => route('publisher.uploads', array_filter([
            'view' => $targetView,
            'device_type' => $deviceType,
            'manufacturer' => $manufacturer !== '' ? $manufacturer : null,
            'keyword' => $keyword !== '' ? $keyword : null,
        ]));

        return Inertia::render('LineWatt/LibraryAdminDatasheets', [
            'roleLabel' => 'Library Publisher',
            'title' => 'Publisher Datasheets',
            'subtitle' => 'Source datasheets grouped by product type and review basket.',
            'basePath' => route('publisher.uploads'),
            'uploadHref' => route('publisher.uploads.new'),
            'searchHref' => route('engineering-search'),
            'showSummaryCards' => false,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('publisher')],
                ['label' => 'Library Data Management'],
                ['label' => 'Datasheets'],
            ],
            'view' => $view,
            'filters' => [
                'keyword' => $keyword,
                'manufacturer' => $manufacturer,
                'device_type' => $deviceType,
                'status' => '',
                'review_status' => '',
                'source_type' => '',
                'pdf_access_mode' => '',
                'uploaded_from' => '',
                'uploaded_to' => '',
            ],
            'summary' => [
                'all' => 0,
                'new_uploads' => 0,
                'pending_review' => 0,
                'failed_compiles' => 0,
                'duplicate_candidates' => 0,
            ],
            'filterOptions' => [
                'statuses' => [],
                'review_statuses' => [],
                'source_types' => [],
                'pdf_access_modes' => [],
            ],
            'views' => [
                ['key' => 'pending_review', 'label' => 'Pending Review', 'href' => $viewHref('pending_review')],
                ['key' => 'rejected', 'label' => 'Rejected', 'href' => $viewHref('rejected')],
                ['key' => 'all', 'label' => 'Show All', 'href' => $viewHref('all')],
            ],
            'datasheets' => $datasheets,
        ]);
    }

    private function publisherDatasheetRow(DeviceDatasheet $datasheet): array
    {
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
            'compile_status' => $datasheet->compiler_version,
            'pdf_access_mode' => $datasheet->pdf_access_mode ?: 'internal_only',
            'source_type' => $datasheet->source_type,
            'uploaded_by' => $datasheet->metadata['uploaded_by'] ?? '—',
            'uploaded_at' => $datasheet->created_at?->toDateTimeString(),
            'filename' => $datasheet->datasheet_original_filename ?: 'datasheet.pdf',
            'duplicate_candidate' => false,
            'actions' => [
                'preview_pdf' => route('publisher.datasheets.review.source-pdf', ['datasheet' => $datasheet->id]),
                'review_compilation' => route('publisher.datasheets.review', ['datasheet' => $datasheet->id]),
                'retry_compile' => null,
                'duplicate_review' => null,
                'replace_datasheet' => null,
                'history' => null,
                'approve_publish' => null,
                'request_changes' => null,
                'reject' => null,
            ],
        ];
    }

    /**
     * @return array<string,int>
     */
    private function emptyStats(): array
    {
        return [
            'compiled' => 0,
            'created' => 0,
            'reviewed' => 0,
            'approved' => 0,
            'rejected_rework' => 0,
            'pending_review' => 0,
            'approval_rate' => 0,
        ];
    }

    private function publisherDatasheetQuery(int $userId): Builder
    {
        return DeviceDatasheet::query()
            ->where('source_type', 'central_curated')
            ->where(function (Builder $query) use ($userId): void {
                $this->applyPublisherMetadataScope($query, $userId);
            });
    }

    private function publisherRecordQuery(int $userId): Builder
    {
        return CompiledDeviceRecord::query()
            ->with('datasheet')
            ->where('source_type', 'central_curated')
            ->where(function (Builder $query) use ($userId): void {
                $this->applyPublisherMetadataScope($query, $userId);
                $query->orWhereHas('datasheet', function (Builder $datasheet) use ($userId): void {
                    $this->applyPublisherMetadataScope($datasheet, $userId);
                });
            });
    }

    private function applyPublisherMetadataScope(Builder $query, int $userId): void
    {
        $table = $query->getModel()->getTable();

        $query
            ->where('metadata->uploaded_by', $userId)
            ->orWhere('metadata->uploaded_by', (string) $userId)
            ->orWhere('metadata->submitted_by', $userId)
            ->orWhere('metadata->submitted_by', (string) $userId)
            ->orWhere('metadata->created_by', $userId)
            ->orWhere('metadata->created_by', (string) $userId)
            ->orWhere('metadata->compiled_by', $userId)
            ->orWhere('metadata->compiled_by', (string) $userId);

        foreach (['created_by', 'compiled_by', 'submitted_by'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $query->orWhere($column, $userId);
            }
        }
    }

    private function approvalRate(int $approved, int $rejected): int
    {
        $total = $approved + $rejected;

        return $total > 0 ? (int) round(($approved / $total) * 100) : 0;
    }

    private function publisherRecordRow(CompiledDeviceRecord $record): array
    {
        return [
            ...$this->recordSummary($record),
            'href' => route('publisher.review.show', ['record' => $record->uuid ?: $record->id]),
            'open_href' => route('publisher.review.show', ['record' => $record->uuid ?: $record->id]),
            'submitted_at' => $record->metadata['submitted_at'] ?? null,
            'reviewed_at' => $record->reviewed_at?->toDateTimeString(),
            'needs_attention_reason' => $this->attentionReason($record),
        ];
    }

    private function attentionReason(CompiledDeviceRecord $record): string
    {
        if (in_array($record->status, ['rejected', 'changes_requested'], true) || in_array((string) $record->review_status, ['rejected', 'changes_requested'], true)) {
            return 'Rejected / Needs Rework';
        }

        if ($record->validation_status === 'errors') {
            return 'Missing or invalid engineering fields';
        }

        if (! $record->device_datasheet_id) {
            return 'Missing datasheet reference';
        }

        if (! $record->manufacturer || ! $record->display_name) {
            return 'Missing required fields';
        }

        if (($record->metadata['duplicate_flagged'] ?? false) || ($record->metadata['duplicate_warning'] ?? false)) {
            return 'Duplicate flagged';
        }

        return 'Pending resubmission';
    }

    private function publisherSubscriberSummary(ManufacturerCompany $company): array
    {
        $names = collect($company->metadata['manufacturer_aliases'] ?? [])
            ->push($company->name)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $datasheetBase = DeviceDatasheet::query()->whereIn('manufacturer', $names);
        $recordBase = CompiledDeviceRecord::query()->whereIn('manufacturer', $names);

        return [
            'id' => $company->id,
            'uuid' => $company->uuid,
            'name' => $company->name,
            'slug' => $company->slug,
            'plan' => $company->plan_label,
            'status_label' => str($company->subscription_status ?: 'pending_invitation')->replace('_', ' ')->title()->toString(),
            'primary_contact_name' => $company->metadata['primary_contact_name'] ?? null,
            'primary_contact_email' => $company->metadata['primary_contact_email'] ?? null,
            'datasheets' => (clone $datasheetBase)->count(),
            'records' => (clone $recordBase)->count(),
            'users_count' => $company->users_count ?? $company->users()->count(),
            'href' => route('manufacturers.show', ['manufacturer' => $company->slug]),
        ];
    }
}
