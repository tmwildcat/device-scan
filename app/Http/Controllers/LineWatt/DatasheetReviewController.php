<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LineWatt\Concerns\BuildsEngineeringRecordPayloads;
use App\LineWatt\Access\LineWattRole;
use App\LineWatt\Activity\ActivityLogger;
use App\LineWatt\Notifications\NotificationManager;
use App\LineWatt\Pdf\DatasheetPdfPolicy;
use App\LineWatt\Publishing\PublishingEvent;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\ReviewComment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DatasheetReviewController extends Controller
{
    use BuildsEngineeringRecordPayloads;

    public function central(Request $request, DeviceDatasheet $datasheet): Response
    {
        return $this->show($request, $datasheet, 'central');
    }

    public function manufacturer(Request $request, DeviceDatasheet $datasheet): Response
    {
        return $this->show($request, $datasheet, 'manufacturer');
    }

    public function publisher(Request $request, DeviceDatasheet $datasheet): Response
    {
        return $this->show($request, $datasheet, 'publisher');
    }

    public function sourcePdfCentral(Request $request, DeviceDatasheet $datasheet): SymfonyResponse
    {
        return $this->sourcePdf($request, $datasheet, 'central', app(DatasheetPdfPolicy::class));
    }

    public function sourcePdfManufacturer(Request $request, DeviceDatasheet $datasheet): SymfonyResponse
    {
        return $this->sourcePdf($request, $datasheet, 'manufacturer', app(DatasheetPdfPolicy::class));
    }

    public function sourcePdfPublisher(Request $request, DeviceDatasheet $datasheet): SymfonyResponse
    {
        return $this->sourcePdf($request, $datasheet, 'publisher', app(DatasheetPdfPolicy::class));
    }

    public function saveCentral(Request $request, DeviceDatasheet $datasheet): RedirectResponse
    {
        return $this->save($request, $datasheet, 'central');
    }

    public function saveManufacturer(Request $request, DeviceDatasheet $datasheet): RedirectResponse
    {
        return $this->save($request, $datasheet, 'manufacturer');
    }

    public function savePublisher(Request $request, DeviceDatasheet $datasheet): RedirectResponse
    {
        return $this->save($request, $datasheet, 'publisher');
    }

    public function submitManufacturer(Request $request, DeviceDatasheet $datasheet): RedirectResponse
    {
        return $this->submitForApproval($request, $datasheet, 'manufacturer');
    }

    public function submitPublisher(Request $request, DeviceDatasheet $datasheet): RedirectResponse
    {
        return $this->submitForApproval($request, $datasheet, 'publisher');
    }

    public function approveCentral(Request $request, DeviceDatasheet $datasheet): RedirectResponse
    {
        $datasheet = $this->findDatasheet($request, $datasheet, 'central');
        $records = $datasheet->compiledRecords()->get();

        if ($records->contains(fn (CompiledDeviceRecord $record): bool => $record->validation_status === 'errors')) {
            return back()->with('error', 'Datasheet has model records with validation errors. Resolve them before publishing.');
        }

        $now = now();
        $datasheet->forceFill([
            'status' => 'published',
            'review_status' => 'approved',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => $now,
            'metadata' => [
                ...($datasheet->metadata ?? []),
                'review_status' => 'approved',
                'published_at' => $now->toIso8601String(),
            ],
        ])->save();

        foreach ($records as $record) {
            $previousStatus = $record->status;
            $record->forceFill([
                'source_type' => 'central_curated',
                'status' => 'published',
                'review_status' => 'approved',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => $now,
                'metadata' => [
                    ...($record->metadata ?? []),
                    'review_status' => 'approved',
                    'published_at' => $now->toIso8601String(),
                ],
            ])->save();

            $this->addReviewComment($request, $record, 'datasheet_published', 'Datasheet approved and published.', $previousStatus, 'published');
            $this->notifySubmitter($record, PublishingEvent::ENGINEERING_RECORD_PUBLISHED, 'Datasheet published', 'The datasheet and related Structured Engineering Data were published.');
        }

        return back()->with('success', 'Datasheet and related Structured Engineering Data published.');
    }

    public function rejectCentral(Request $request, DeviceDatasheet $datasheet): RedirectResponse
    {
        $validated = $request->validate(['comment' => ['required', 'string', 'max:2000']]);

        return $this->transitionCentral($request, $datasheet, 'rejected', 'rejected', 'datasheet_rejected', $validated['comment'], PublishingEvent::ENGINEERING_RECORD_REJECTED, true);
    }

    public function requestChangesCentral(Request $request, DeviceDatasheet $datasheet): RedirectResponse
    {
        $validated = $request->validate(['comment' => ['required', 'string', 'max:2000']]);

        return $this->transitionCentral($request, $datasheet, 'changes_requested', 'changes_requested', 'datasheet_changes_requested', $validated['comment'], PublishingEvent::ENGINEERING_RECORD_CHANGES_REQUESTED, true);
    }

    private function show(Request $request, DeviceDatasheet $datasheet, string $workspace): Response
    {
        $datasheet = $this->findDatasheet($request, $datasheet, $workspace);
        $pdfPolicy = app(DatasheetPdfPolicy::class);
        $records = $datasheet->compiledRecords()
            ->latest('updated_at')
            ->get();

        return Inertia::render('LineWatt/DatasheetReview', [
            'workspace' => $workspace,
            'workspaceLabel' => match ($workspace) {
                'manufacturer' => 'Manufacturer Datasheet Review',
                'publisher' => 'Publisher Datasheet Review',
                default => 'Library Datasheet Review',
            },
            'roleLabel' => LineWattRole::label($request->user()?->role),
            'company' => $workspace === 'manufacturer' ? $this->companyPayload($request) : null,
            'datasheet' => $this->datasheetPayload($datasheet, $request),
            'pdfPolicy' => $pdfPolicy->viewPayload(
                $request->user(),
                $datasheet,
                match ($workspace) {
                    'manufacturer' => route('admin.manufacturer.datasheets.review.source-pdf', ['datasheet' => $datasheet->id]),
                    'publisher' => route('publisher.datasheets.review.source-pdf', ['datasheet' => $datasheet->id]),
                    default => route('admin.library.datasheets.review.source-pdf', ['datasheet' => $datasheet->id]),
                },
            ),
            'summary' => $this->summaryPayload($datasheet, $records),
            'coverage' => $this->coveragePayload($records),
            'warnings' => $this->warningsPayload($datasheet, $records),
            'records' => $records
                ->map(fn (CompiledDeviceRecord $record): array => [
                    ...$this->recordSummary($record),
                    'open_href' => route('records.show', ['record' => $record->uuid ?: $record->id]),
                    'review_model_href' => match ($workspace) {
                        'manufacturer' => route('admin.manufacturer.engineering-data.review', ['record' => $record->uuid ?: $record->id]),
                        'publisher' => route('publisher.review.show', ['record' => $record->uuid ?: $record->id]),
                        default => route('admin.library.review', ['record' => $record->uuid ?: $record->id]),
                    },
                    'history_href' => $workspace === 'manufacturer'
                        ? route('admin.manufacturer.datasheets.show', ['datasheet' => $datasheet->id, 'tab' => 'History'])
                        : route('admin.library.datasheets.review', ['datasheet' => $datasheet->id]),
                ])
                ->all(),
            'routes' => [
                'sourcePdf' => match ($workspace) {
                    'manufacturer' => route('admin.manufacturer.datasheets.review.source-pdf', ['datasheet' => $datasheet->id]),
                    'publisher' => route('publisher.datasheets.review.source-pdf', ['datasheet' => $datasheet->id]),
                    default => route('admin.library.datasheets.review.source-pdf', ['datasheet' => $datasheet->id]),
                },
                'save' => match ($workspace) {
                    'manufacturer' => route('admin.manufacturer.datasheets.review.save', ['datasheet' => $datasheet->id]),
                    'publisher' => route('publisher.datasheets.review.save', ['datasheet' => $datasheet->id]),
                    default => route('admin.library.datasheets.review.save', ['datasheet' => $datasheet->id]),
                },
                'submit' => match ($workspace) {
                    'manufacturer' => route('admin.manufacturer.datasheets.review.submit', ['datasheet' => $datasheet->id]),
                    'publisher' => route('publisher.datasheets.review.submit', ['datasheet' => $datasheet->id]),
                    default => null,
                },
                'approve' => $workspace === 'central'
                    ? route('admin.library.datasheets.review.approve', ['datasheet' => $datasheet->id])
                    : null,
                'reject' => $workspace === 'central'
                    ? route('admin.library.datasheets.review.reject', ['datasheet' => $datasheet->id])
                    : null,
                'requestChanges' => $workspace === 'central'
                    ? route('admin.library.datasheets.review.changes-requested', ['datasheet' => $datasheet->id])
                    : null,
                'back' => match ($workspace) {
                    'manufacturer' => route('admin.manufacturer.datasheets'),
                    'publisher' => route('publisher.uploads', ['device_type' => $datasheet->device_type, 'view' => 'pending_review']),
                    default => route('admin.library.datasheets.all', ['tab' => $datasheet->device_type === 'inverter' ? 'inverters' : 'modules']),
                },
            ],
        ]);
    }

    private function save(Request $request, DeviceDatasheet $datasheet, string $workspace): RedirectResponse
    {
        $datasheet = $this->findDatasheet($request, $datasheet, $workspace);
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $datasheet->forceFill([
            'review_status' => $workspace === 'central' ? 'librarian_reviewed' : 'publisher_reviewed',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'metadata' => [
                ...($datasheet->metadata ?? []),
                'datasheet_review_notes' => $validated['notes'] ?? null,
                'review_status' => $workspace === 'central' ? 'librarian_reviewed' : 'publisher_reviewed',
                'reviewed_at' => now()->toIso8601String(),
            ],
        ])->save();

        return back()->with('success', 'Datasheet review saved.');
    }

    private function submitForApproval(Request $request, DeviceDatasheet $datasheet, string $workspace): RedirectResponse
    {
        $datasheet = $this->findDatasheet($request, $datasheet, $workspace);
        $now = now();

        $datasheet->forceFill([
            'status' => 'submitted_for_approval',
            'review_status' => 'submitted',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => $now,
            'metadata' => [
                ...($datasheet->metadata ?? []),
                'review_status' => 'submitted',
                'submitted_by' => $request->user()?->id,
                'submitted_at' => $now->toIso8601String(),
            ],
        ])->save();

        $firstRecord = null;
        foreach ($datasheet->compiledRecords as $record) {
            $firstRecord ??= $record;
            $previousStatus = $record->status;
            $record->forceFill([
                'status' => 'submitted_for_approval',
                'review_status' => 'submitted',
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => $now,
                'metadata' => [
                    ...($record->metadata ?? []),
                    'review_status' => 'submitted',
                    'submitted_by' => $request->user()?->id,
                    'submitted_at' => $now->toIso8601String(),
                ],
            ])->save();
            $this->addReviewComment($request, $record, 'datasheet_submitted_for_approval', 'Datasheet submitted for Librarian approval.', $previousStatus, 'submitted_for_approval');
        }

        if ($firstRecord) {
            $activity = app(ActivityLogger::class)->log(PublishingEvent::ENGINEERING_RECORD_SUBMITTED_FOR_APPROVAL, $request->user(), $firstRecord);
            app(NotificationManager::class)->notifyLibrarians(
                PublishingEvent::ENGINEERING_RECORD_SUBMITTED_FOR_APPROVAL,
                'Datasheet submitted for approval',
                ($datasheet->product_name ?: $datasheet->datasheet_original_filename ?: 'Datasheet').' is ready for Librarian review.',
                route('admin.library.datasheets.review', ['datasheet' => $datasheet->id]),
                $activity,
                true,
            );
        }

        return back()->with('success', 'Datasheet submitted for Librarian approval.');
    }

    private function transitionCentral(Request $request, DeviceDatasheet $datasheet, string $status, string $reviewStatus, string $action, string $comment, string $event, bool $emailSubmitter): RedirectResponse
    {
        $datasheet = $this->findDatasheet($request, $datasheet, 'central');
        $now = now();

        $datasheet->forceFill([
            'status' => $status,
            'review_status' => $reviewStatus,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => $now,
            'metadata' => [
                ...($datasheet->metadata ?? []),
                'review_status' => $reviewStatus,
                $reviewStatus.'_at' => $now->toIso8601String(),
            ],
        ])->save();

        foreach ($datasheet->compiledRecords as $record) {
            $previousStatus = $record->status;
            $record->forceFill([
                'status' => $status,
                'review_status' => $reviewStatus,
                'reviewed_by' => $request->user()?->id,
                'reviewed_at' => $now,
                'metadata' => [
                    ...($record->metadata ?? []),
                    'review_status' => $reviewStatus,
                    $reviewStatus.'_at' => $now->toIso8601String(),
                ],
            ])->save();

            $this->addReviewComment($request, $record, $action, $comment, $previousStatus, $status);
            $this->notifySubmitter($record, $event, match ($event) {
                PublishingEvent::ENGINEERING_RECORD_REJECTED => 'Datasheet rejected',
                PublishingEvent::ENGINEERING_RECORD_CHANGES_REQUESTED => 'Changes requested',
                default => 'Datasheet updated',
            }, $comment, $emailSubmitter);
        }

        return back()->with('success', str_replace('_', ' ', ucfirst($status)).' saved.');
    }

    private function sourcePdf(Request $request, DeviceDatasheet $datasheet, string $workspace, DatasheetPdfPolicy $pdfPolicy): SymfonyResponse
    {
        $datasheet = $this->findDatasheet($request, $datasheet, $workspace);
        abort_unless($pdfPolicy->canInternalPreview($request->user(), $datasheet), 403);
        abort_unless($datasheet->datasheet_disk && $datasheet->datasheet_path, 404);

        $disk = Storage::disk($datasheet->datasheet_disk);
        abort_unless($disk->exists($datasheet->datasheet_path), 404);

        return response($disk->get($datasheet->datasheet_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$this->safeFilename($datasheet->datasheet_original_filename ?: 'datasheet.pdf').'"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function findDatasheet(Request $request, DeviceDatasheet $datasheet, string $workspace): DeviceDatasheet
    {
        return $this->visibleDatasheetQuery($request, $workspace)
            ->whereKey($datasheet->id)
            ->firstOrFail();
    }

    /**
     * @return Builder<DeviceDatasheet>
     */
    private function visibleDatasheetQuery(Request $request, string $workspace): Builder
    {
        $user = $request->user();
        $company = $user?->manufacturerCompany;
        $isPlatformOperator = $user && in_array($user->role, [LineWattRole::SUPER_ADMIN, LineWattRole::ADMIN, LineWattRole::LIBRARIAN], true);

        return DeviceDatasheet::query()
            ->with(['compiledRecords', 'compiledRecords.reviewComments'])
            ->whereIn('source_type', ['central_curated', 'partner_submitted'])
            ->when($workspace === 'manufacturer' && ! $isPlatformOperator && $company, fn (Builder $query) => $query->whereIn('manufacturer', $this->manufacturerNames($company)))
            ->when($workspace === 'manufacturer' && ! $isPlatformOperator && ! $company, fn (Builder $query) => $query->whereRaw('1 = 0'));
    }

    /**
     * @return array<string,mixed>
     */
    private function datasheetPayload(DeviceDatasheet $datasheet, Request $request): array
    {
        $uploaderId = $datasheet->metadata['uploaded_by'] ?? $datasheet->metadata['submitted_by'] ?? null;

        return [
            'id' => $datasheet->id,
            'uuid' => $datasheet->uuid,
            'manufacturer' => $datasheet->manufacturer,
            'title' => $datasheet->product_name ?: $datasheet->datasheet_original_filename ?: 'Datasheet',
            'family_series' => $datasheet->series,
            'revision' => $datasheet->metadata['revision'] ?? 'v1',
            'language' => $datasheet->metadata['language'] ?? 'English',
            'publication_date' => $datasheet->metadata['publication_date'] ?? null,
            'source_type' => $datasheet->source_type,
            'uploaded_by' => $uploaderId,
            'uploaded_at' => $datasheet->created_at?->toDateTimeString(),
            'status' => $datasheet->status,
            'review_status' => $datasheet->review_status ?? $datasheet->metadata['review_status'] ?? null,
            'notes' => $datasheet->metadata['datasheet_review_notes'] ?? '',
            'manufacturer_mismatch' => $datasheet->metadata['manufacturer_mismatch_detected'] ?? false,
            'detected_manufacturer' => $datasheet->metadata['detected_manufacturer'] ?? null,
            'pdf_access_mode' => $datasheet->pdf_access_mode,
            'source_url' => $datasheet->source_url,
            'permission_status' => $datasheet->permission_status,
        ];
    }

    /**
     * @param  iterable<CompiledDeviceRecord>  $records
     * @return array<string,mixed>
     */
    private function summaryPayload(DeviceDatasheet $datasheet, iterable $records): array
    {
        $collection = collect($records);

        return [
            'models_found' => $collection->filter(fn (CompiledDeviceRecord $record) => $record->display_name || $record->model_name || $record->model_series)->count(),
            'compiled_records_created' => $collection->count(),
            'device_type' => $datasheet->device_type,
            'compiler_version' => $collection->pluck('compiler_version')->filter()->unique()->implode(', ') ?: $datasheet->compiler_version,
            'quality_score' => round((float) $collection->pluck('validation_score')->filter(fn ($score) => $score !== null)->avg(), 1) ?: null,
            'quality_grade' => $collection->pluck('validation_grade')->filter()->unique()->implode(', ') ?: null,
            'validation_status' => $collection->pluck('validation_status')->filter()->unique()->implode(', ') ?: null,
            'warning_count' => $this->issueCount($collection, 'warning'),
            'error_count' => $this->issueCount($collection, 'error'),
        ];
    }

    /**
     * @param  iterable<CompiledDeviceRecord>  $records
     * @return array<string,bool>
     */
    private function coveragePayload(iterable $records): array
    {
        $coverage = [
            'stc_electrical_found' => false,
            'mechanical_found' => false,
            'operating_conditions_found' => false,
            'temperature_characteristics_found' => false,
            'warranty_found' => false,
            'certifications_found' => false,
            'dc_input_found' => false,
            'ac_output_found' => false,
            'protection_found' => false,
            'rated_power_conditions_found' => false,
            'central_specific_found' => false,
        ];

        foreach ($records as $record) {
            $json = $this->readCompiledJson($record) ?? [];
            $coverage['stc_electrical_found'] = $coverage['stc_electrical_found'] || ! empty($json['electrical_stc']);
            $coverage['mechanical_found'] = $coverage['mechanical_found'] || ! empty($json['mechanical']);
            $coverage['operating_conditions_found'] = $coverage['operating_conditions_found'] || ! empty($json['operating_conditions']);
            $coverage['temperature_characteristics_found'] = $coverage['temperature_characteristics_found'] || ! empty($json['temperature_characteristics']);
            $coverage['warranty_found'] = $coverage['warranty_found'] || ! empty($json['warranty']);
            $coverage['certifications_found'] = $coverage['certifications_found'] || ! empty($json['certifications']);
            $coverage['dc_input_found'] = $coverage['dc_input_found'] || ! empty($json['dc_input']);
            $coverage['ac_output_found'] = $coverage['ac_output_found'] || ! empty($json['ac_output']);
            $coverage['protection_found'] = $coverage['protection_found'] || ! empty($json['protection']);
            $coverage['rated_power_conditions_found'] = $coverage['rated_power_conditions_found'] || ! empty($json['rated_power_conditions']);
            $coverage['central_specific_found'] = $coverage['central_specific_found'] || ! empty($json['central_specific']);
        }

        return $coverage;
    }

    /**
     * @param  iterable<CompiledDeviceRecord>  $records
     * @return array<int,array<string,mixed>>
     */
    private function warningsPayload(DeviceDatasheet $datasheet, iterable $records): array
    {
        $warnings = [];

        if ($datasheet->metadata['manufacturer_mismatch_detected'] ?? false) {
            $warnings[] = [
                'severity' => 'warning',
                'code' => 'manufacturer_mismatch_detected',
                'message' => 'Compiler-detected manufacturer differs from the owning manufacturer account.',
            ];
        }

        foreach ($records as $record) {
            $json = $this->readCompiledJson($record) ?? [];
            foreach (($json['extraction_warnings'] ?? []) as $warning) {
                $warnings[] = [
                    'severity' => 'warning',
                    'code' => is_string($warning) ? $warning : ($warning['code'] ?? 'extraction_warning'),
                    'message' => is_string($warning) ? $warning : ($warning['message'] ?? $warning['code'] ?? 'Extraction warning'),
                    'record' => $record->display_name ?: $record->model_name ?: $record->model_series,
                ];
            }
            foreach (($json['validation']['issues'] ?? []) as $issue) {
                $warnings[] = [
                    'severity' => $issue['severity'] ?? 'warning',
                    'code' => $issue['code'] ?? 'validation_issue',
                    'message' => $issue['message'] ?? 'Validation issue',
                    'record' => $record->display_name ?: $record->model_name ?: $record->model_series,
                ];
            }
        }

        return $warnings;
    }

    private function issueCount($records, string $severity): int
    {
        return (int) collect($records)->sum(function (CompiledDeviceRecord $record) use ($severity): int {
            $issues = ($this->readCompiledJson($record) ?? [])['validation']['issues'] ?? [];

            return collect($issues)->filter(fn ($issue): bool => ($issue['severity'] ?? null) === $severity)->count();
        });
    }

    private function addReviewComment(Request $request, CompiledDeviceRecord $record, string $action, ?string $comment, ?string $previousStatus, ?string $newStatus): ReviewComment
    {
        return ReviewComment::create([
            'compiled_device_record_id' => $record->id,
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'comment' => $comment,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
        ]);
    }

    private function notifySubmitter(CompiledDeviceRecord $record, string $event, string $title, ?string $body, bool $email = false): void
    {
        $activity = app(ActivityLogger::class)->log($event, auth()->user(), $record);
        app(NotificationManager::class)->notifySubmitter($record, $event, $title, $body, route('records.show', ['record' => $record->uuid ?: $record->id]), $activity, $email);
    }

    /**
     * @return list<string>
     */
    private function manufacturerNames($company): array
    {
        return collect($company?->metadata['manufacturer_aliases'] ?? [])
            ->push($company?->name)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function safeFilename(string $filename): string
    {
        return str_replace(['"', "\r", "\n"], '', $filename);
    }

    /**
     * @return array<string,mixed>
     */
    private function companyPayload(Request $request): array
    {
        $user = $request->user();
        $company = $user?->manufacturerCompany;
        $plan = $company?->plan_code ?: 'pro';

        return [
            'name' => $company?->name ?: 'Manufacturer',
            'plan_code' => $plan,
            'plan_label' => match ($plan) {
                'enterprise' => 'Enterprise',
                default => 'Pro',
            },
            'manufacturer_role_label' => match ($user?->manufacturer_role) {
                'manufacturer_admin' => 'Manufacturer Admin',
                'manufacturer_user' => 'Manufacturer User',
                default => LineWattRole::label($user?->role),
            },
            'can_upgrade' => $user?->manufacturer_role === 'manufacturer_admin' && $plan === 'pro',
            'upgrade_message' => $user?->manufacturer_role === 'manufacturer_user'
                ? 'Please contact your Manufacturer Administrator to upgrade your subscription.'
                : null,
        ];
    }
}
