<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\LineWattRole;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class LibraryPublisherAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $publishers = User::query()
            ->where('role', LineWattRole::LIBRARY_PUBLISHER)
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $publisher): array => $this->publisherRow($publisher))
            ->toArray();

        return Inertia::render('LineWatt/LibraryAdminPublishers', [
            'roleLabel' => LineWattRole::label($request->user()?->role),
            'publishers' => $publishers,
            'createHref' => route('admin.library.publishers.create'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('LineWatt/LibraryAdminPublisherCreate', [
            'roleLabel' => LineWattRole::label($request->user()?->role),
            'backHref' => route('admin.library.publishers'),
        ]);
    }

    public function show(Request $request, User $publisher): Response
    {
        abort_unless($publisher->role === LineWattRole::LIBRARY_PUBLISHER, 404);

        $datasheets = $this->publisherDatasheetQuery($publisher);
        $records = $this->publisherRecordQuery($publisher);

        $stats = [
            'datasheets_compiled' => (clone $datasheets)->whereIn('status', ['compiled', 'publisher_review', 'review_required', 'submitted_for_approval', 'approved', 'published'])->count(),
            'datasheets_pending_review' => (clone $datasheets)->where(function (Builder $query): void {
                $query->whereIn('status', ['uploaded', 'compiled', 'publisher_review', 'review_required'])
                    ->orWhereNull('review_status')
                    ->orWhereIn('review_status', ['not_reviewed', 'pending_review', 'publisher_reviewed']);
            })->count(),
            'datasheets_submitted' => (clone $datasheets)->where(function (Builder $query): void {
                $query->where('status', 'submitted_for_approval')->orWhere('review_status', 'submitted');
            })->count(),
            'datasheets_approved' => (clone $datasheets)->whereIn('status', ['approved', 'published'])->count(),
            'datasheets_rework' => (clone $datasheets)->where(function (Builder $query): void {
                $query->whereIn('status', ['rejected', 'changes_requested'])->orWhereIn('review_status', ['rejected', 'changes_requested']);
            })->count(),
            'records_created' => (clone $records)->count(),
            'records_pending_review' => (clone $records)->where(function (Builder $query): void {
                $query->whereIn('status', ['compiled', 'publisher_review', 'review_required'])
                    ->orWhereNull('review_status')
                    ->orWhereIn('review_status', ['not_reviewed', 'pending_review', 'publisher_reviewed']);
            })->count(),
            'records_submitted' => (clone $records)->where(function (Builder $query): void {
                $query->where('status', 'submitted_for_approval')->orWhere('review_status', 'submitted');
            })->count(),
            'records_approved' => (clone $records)->whereIn('status', ['approved', 'published'])->count(),
            'records_rework' => (clone $records)->where(function (Builder $query): void {
                $query->whereIn('status', ['rejected', 'changes_requested'])->orWhereIn('review_status', ['rejected', 'changes_requested']);
            })->count(),
        ];

        $unreviewed = (clone $records)
            ->whereIn('status', ['compiled', 'publisher_review', 'review_required'])
            ->where(function (Builder $query): void {
                $query->whereNull('review_status')->orWhereIn('review_status', ['not_reviewed', 'pending_review']);
            })
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (CompiledDeviceRecord $record): array => $this->recordRow($record))
            ->all();

        $attention = (clone $records)
            ->where(function (Builder $query): void {
                $query
                    ->whereIn('status', ['rejected', 'changes_requested'])
                    ->orWhereIn('review_status', ['rejected', 'changes_requested'])
                    ->orWhere('validation_status', 'errors')
                    ->orWhereNull('manufacturer')
                    ->orWhereNull('display_name')
                    ->orWhere('metadata->duplicate_flagged', true)
                    ->orWhere('metadata->duplicate_warning', true);
            })
            ->latest('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (CompiledDeviceRecord $record): array => $this->recordRow($record))
            ->all();

        $recentDatasheets = (clone $datasheets)
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (DeviceDatasheet $datasheet): array => [
                'id' => $datasheet->id,
                'title' => $datasheet->product_name ?: $datasheet->datasheet_original_filename ?: 'Untitled datasheet',
                'manufacturer' => $datasheet->manufacturer ?: 'Manufacturer pending',
                'device_type' => $datasheet->device_type,
                'status' => $datasheet->status,
                'review_status' => $datasheet->review_status,
                'updated_at' => $datasheet->updated_at?->toDateString(),
                'href' => route('admin.library.datasheets.review', ['datasheet' => $datasheet->id]),
            ])
            ->all();

        return Inertia::render('LineWatt/LibraryAdminPublisherDetail', [
            'roleLabel' => LineWattRole::label($request->user()?->role),
            'publisher' => $this->publisherRow($publisher),
            'stats' => $stats,
            'unreviewed' => $unreviewed,
            'attention' => $attention,
            'recentDatasheets' => $recentDatasheets,
        ]);
    }

    private function publisherRow(User $publisher): array
    {
        $datasheets = $this->publisherDatasheetQuery($publisher);
        $records = $this->publisherRecordQuery($publisher);

        return [
            'id' => $publisher->id,
            'name' => $publisher->name,
            'email' => $publisher->email,
            'status' => $publisher->subscription_status ?: 'active',
            'datasheets' => (clone $datasheets)->count(),
            'records' => (clone $records)->count(),
            'needs_attention' => (clone $records)->where(function (Builder $query): void {
                $query->whereIn('status', ['rejected', 'changes_requested'])
                    ->orWhereIn('review_status', ['rejected', 'changes_requested'])
                    ->orWhere('validation_status', 'errors');
            })->count(),
            'last_activity' => $publisher->updated_at?->toDateString(),
            'href' => route('admin.library.publishers.show', ['publisher' => $publisher]),
        ];
    }

    private function publisherDatasheetQuery(User $publisher): Builder
    {
        return DeviceDatasheet::query()
            ->where('source_type', 'central_curated')
            ->where(function (Builder $query) use ($publisher): void {
                $this->applyPublisherScope($query, $publisher);
            });
    }

    private function publisherRecordQuery(User $publisher): Builder
    {
        return CompiledDeviceRecord::query()
            ->with('datasheet')
            ->where('source_type', 'central_curated')
            ->where(function (Builder $query) use ($publisher): void {
                $this->applyPublisherScope($query, $publisher);
                $query->orWhereHas('datasheet', function (Builder $datasheet) use ($publisher): void {
                    $this->applyPublisherScope($datasheet, $publisher);
                });
            });
    }

    private function applyPublisherScope(Builder $query, User $publisher): void
    {
        $table = $query->getModel()->getTable();
        $userId = $publisher->id;

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

    private function recordRow(CompiledDeviceRecord $record): array
    {
        return [
            'id' => $record->id,
            'uuid' => $record->uuid,
            'display_name' => $record->display_name ?: $record->model_name ?: 'Untitled Engineering Data',
            'manufacturer' => $record->manufacturer ?: 'Manufacturer pending',
            'device_type' => $record->device_type,
            'status' => $record->status,
            'review_status' => $record->review_status,
            'validation_status' => $record->validation_status,
            'updated_at' => $record->updated_at?->toDateString(),
            'href' => route('admin.library.review', ['record' => $record->uuid ?: $record->id]),
        ];
    }
}
