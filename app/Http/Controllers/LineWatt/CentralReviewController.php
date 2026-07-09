<?php

namespace App\Http\Controllers\LineWatt;

use App\DeviceScan\Storage\DeviceScanArtifactStorage;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LineWatt\Concerns\BuildsEngineeringRecordPayloads;
use App\LineWatt\Activity\ActivityLogger;
use App\LineWatt\Access\LineWattRole;
use App\LineWatt\Notifications\NotificationManager;
use App\LineWatt\Pdf\DatasheetPdfPolicy;
use App\LineWatt\Publishing\PublishingEvent;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\ManufacturerCompany;
use App\Models\ReviewComment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CentralReviewController extends Controller
{
    use BuildsEngineeringRecordPayloads;

    public function central(Request $request, string $record): Response
    {
        return $this->show($request, $record, 'central');
    }

    public function myLibrary(Request $request, string $record): Response
    {
        return $this->show($request, $record, 'my-library');
    }

    public function partner(Request $request, string $record): Response
    {
        return $this->show($request, $record, 'partner');
    }

    public function manufacturer(Request $request, string $record): Response
    {
        return $this->show($request, $record, 'manufacturer');
    }

    public function publisher(Request $request, string $record): Response
    {
        return $this->show($request, $record, 'publisher');
    }

    public function saveCentral(Request $request, string $record, DeviceScanArtifactStorage $storage): RedirectResponse
    {
        return $this->save($request, $record, 'central', $storage);
    }

    public function saveMyLibrary(Request $request, string $record, DeviceScanArtifactStorage $storage): RedirectResponse
    {
        return $this->save($request, $record, 'my-library', $storage);
    }

    public function savePartner(Request $request, string $record, DeviceScanArtifactStorage $storage): RedirectResponse
    {
        return $this->save($request, $record, 'partner', $storage);
    }

    public function saveManufacturer(Request $request, string $record, DeviceScanArtifactStorage $storage): RedirectResponse
    {
        return $this->save($request, $record, 'manufacturer', $storage);
    }

    public function savePublisher(Request $request, string $record, DeviceScanArtifactStorage $storage): RedirectResponse
    {
        return $this->save($request, $record, 'publisher', $storage);
    }

    public function sourcePdfCentral(Request $request, string $record): SymfonyResponse
    {
        return $this->sourcePdf($request, $record, 'central');
    }

    public function sourcePdfMyLibrary(Request $request, string $record): SymfonyResponse
    {
        return $this->sourcePdf($request, $record, 'my-library');
    }

    public function sourcePdfPartner(Request $request, string $record): SymfonyResponse
    {
        return $this->sourcePdf($request, $record, 'partner');
    }

    public function sourcePdfManufacturer(Request $request, string $record): SymfonyResponse
    {
        return $this->sourcePdf($request, $record, 'manufacturer');
    }

    public function sourcePdfPublisher(Request $request, string $record): SymfonyResponse
    {
        return $this->sourcePdf($request, $record, 'publisher');
    }

    public function approveCentral(Request $request, string $record): RedirectResponse
    {
        $compiledRecord = $this->findRecord($request, $record, 'central');

        if ($compiledRecord->validation_status === 'errors') {
            return back()->with('error', 'Records with validation errors cannot be published from this placeholder review workflow.');
        }

        $compiledRecord->forceFill([
            'source_type' => 'central_curated',
            'status' => 'published',
            'review_status' => 'approved',
            'metadata' => [
                ...($compiledRecord->metadata ?? []),
                'review_status' => 'approved',
                'reviewed_at' => now()->toIso8601String(),
                'published_at' => now()->toIso8601String(),
            ],
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ])->save();

        $this->commentAndNotify($request, $compiledRecord, PublishingEvent::ENGINEERING_RECORD_PUBLISHED, 'published', 'Approved and published.');

        return back()->with('success', 'Engineering Record approved and published.');
    }

    public function rejectCentral(Request $request, string $record): RedirectResponse
    {
        $validated = $request->validate(['comment' => ['required', 'string', 'max:2000']]);
        $compiledRecord = $this->findRecord($request, $record, 'central');
        $compiledRecord->forceFill([
            'status' => 'rejected',
            'review_status' => 'rejected',
            'metadata' => [
                ...($compiledRecord->metadata ?? []),
                'review_status' => 'rejected',
                'rejected_at' => now()->toIso8601String(),
            ],
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ])->save();

        $this->commentAndNotify($request, $compiledRecord, PublishingEvent::ENGINEERING_RECORD_REJECTED, 'rejected', $validated['comment'], emailSubmitter: true);

        return back()->with('success', 'Engineering Record rejected.');
    }

    public function requestChangesCentral(Request $request, string $record): RedirectResponse
    {
        $validated = $request->validate(['comment' => ['required', 'string', 'max:2000']]);
        $compiledRecord = $this->findRecord($request, $record, 'central');
        $previousStatus = $compiledRecord->status;
        $compiledRecord->forceFill([
            'status' => 'changes_requested',
            'review_status' => 'changes_requested',
            'metadata' => [
                ...($compiledRecord->metadata ?? []),
                'review_status' => 'changes_requested',
                'changes_requested_at' => now()->toIso8601String(),
            ],
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ])->save();

        $this->addReviewComment($request, $compiledRecord, 'changes_requested', $validated['comment'], $previousStatus, 'changes_requested');
        $this->notifyTransition($compiledRecord, PublishingEvent::ENGINEERING_RECORD_CHANGES_REQUESTED, 'Changes requested', $validated['comment'], true);

        return back()->with('success', 'Changes requested and submitter notified.');
    }

    public function submitForApproval(Request $request, string $record): RedirectResponse
    {
        $workspace = $request->routeIs('publisher.*') ? 'publisher' : 'manufacturer';
        $compiledRecord = $this->findRecord($request, $record, $workspace);
        $previousStatus = $compiledRecord->status;

        $compiledRecord->forceFill([
            'status' => 'submitted_for_approval',
            'review_status' => 'submitted',
            'metadata' => [
                ...($compiledRecord->metadata ?? []),
                'review_status' => 'submitted',
                'submitted_by' => $request->user()?->id,
                'submitted_at' => now()->toIso8601String(),
            ],
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ])->save();

        $this->addReviewComment($request, $compiledRecord, 'submitted_for_approval', 'Submitted for Librarian approval.', $previousStatus, 'submitted_for_approval');
        $activity = app(ActivityLogger::class)->log(PublishingEvent::ENGINEERING_RECORD_SUBMITTED_FOR_APPROVAL, $request->user(), $compiledRecord);
        app(NotificationManager::class)->notifyLibrarians(
            PublishingEvent::ENGINEERING_RECORD_SUBMITTED_FOR_APPROVAL,
            'Engineering Record submitted for approval',
            ($compiledRecord->display_name ?: 'Engineering Record').' is ready for Librarian review.',
            route('admin.library.review', ['record' => $compiledRecord->uuid ?: $compiledRecord->id]),
            $activity,
            true,
        );

        return back()->with('success', 'Submitted for Librarian approval.');
    }

    public function approveMyLibrary(Request $request, string $record): RedirectResponse
    {
        $compiledRecord = $this->findRecord($request, $record, 'my-library');
        $compiledRecord->forceFill([
            'metadata' => [
                ...($compiledRecord->metadata ?? []),
                'review_status' => 'reviewed',
                'reviewed_at' => now()->toIso8601String(),
            ],
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ])->save();

        return back()->with('success', 'Private Engineering Record marked reviewed.');
    }

    private function show(Request $request, string $record, string $workspace): Response
    {
        $compiledRecord = $this->findRecord($request, $record, $workspace);
        $review = $compiledRecord->metadata['review_artifact'] ?? null;
        $pdfPolicy = app(DatasheetPdfPolicy::class);
        $datasheet = $compiledRecord->datasheet;
        $recordRouteKey = $workspace === 'central' ? $compiledRecord->id : ($compiledRecord->uuid ?: $compiledRecord->id);
        $sourcePdfRoute = $datasheet && $pdfPolicy->canInternalPreview($request->user(), $datasheet)
            ? match ($workspace) {
                'my-library' => route('my-library.records.review.source-pdf', ['record' => $recordRouteKey]),
                'partner' => route('partner.submissions.review.source-pdf', ['record' => $recordRouteKey]),
                'publisher' => route('publisher.review.source-pdf', ['record' => $recordRouteKey]),
                'manufacturer' => route('admin.manufacturer.engineering-data.review.source-pdf', ['record' => $recordRouteKey]),
                default => route('admin.library.review.source-pdf', ['record' => $recordRouteKey]),
            }
            : null;

        return Inertia::render('LineWatt/CentralReview', [
            'workspace' => $workspace,
            'workspaceLabel' => match ($workspace) {
                'my-library' => 'My Private Datasets Review',
                'partner' => 'Partner Submission Review',
                'manufacturer' => 'Manufacturer Engineering Review',
                default => 'Central Review',
            },
            'record' => $this->recordSummary($compiledRecord),
            'compiledSummary' => $this->compiledJsonSummary($compiledRecord),
            'presentation' => $this->compiledJsonPresentation($compiledRecord),
            'reviewArtifact' => is_array($review) ? $review : null,
            'reviewComments' => $compiledRecord->reviewComments()
                ->with('actor:id,name,email')
                ->latest('created_at')
                ->get()
                ->map(fn (ReviewComment $comment): array => [
                    'id' => $comment->id,
                    'action' => $comment->action,
                    'comment' => $comment->comment,
                    'actor' => $comment->actor?->name ?: 'System',
                    'created_at' => $comment->created_at?->toDateTimeString(),
                ])
                ->all(),
            'pdfPolicy' => $datasheet ? $pdfPolicy->viewPayload($request->user(), $datasheet, $sourcePdfRoute) : null,
            'libraryDebug' => (bool) config('linewatt-library.debug'),
            'routes' => [
                'sourcePdf' => $sourcePdfRoute,
                'save' => match ($workspace) {
                    'my-library' => route('my-library.records.review.save', ['record' => $recordRouteKey]),
                    'partner' => route('partner.submissions.review.save', ['record' => $recordRouteKey]),
                    'publisher' => route('publisher.review.save', ['record' => $recordRouteKey]),
                    'manufacturer' => route('admin.manufacturer.engineering-data.review.save', ['record' => $recordRouteKey]),
                    default => route('admin.library.review.save', ['record' => $recordRouteKey]),
                },
                'submit' => match ($workspace) {
                    'publisher' => route('publisher.review.submit', ['record' => $recordRouteKey]),
                    'manufacturer' => route('admin.manufacturer.engineering-data.review.submit', ['record' => $recordRouteKey]),
                    default => null,
                },
                'approve' => match ($workspace) {
                    'my-library' => route('my-library.records.review.approve', ['record' => $recordRouteKey]),
                    'central' => route('admin.library.review.approve', ['record' => $recordRouteKey]),
                    default => null,
                },
                'reject' => $workspace === 'central'
                    ? route('admin.library.review.reject', ['record' => $recordRouteKey])
                    : null,
                'requestChanges' => $workspace === 'central'
                    ? route('admin.library.review.changes-requested', ['record' => $recordRouteKey])
                    : null,
                'back' => match ($workspace) {
                    'my-library' => route('my-library'),
                    'partner' => route('partner'),
                    'publisher' => route('publisher'),
                    'manufacturer' => $compiledRecord->datasheet
                        ? route('admin.manufacturer.datasheets.show', ['datasheet' => $compiledRecord->datasheet->id])
                        : route('admin.manufacturer.datasheets'),
                    default => route('central-library'),
                },
            ],
        ]);
    }

    private function sourcePdf(Request $request, string $record, string $workspace): SymfonyResponse
    {
        $compiledRecord = $this->findRecord($request, $record, $workspace);
        $datasheet = $compiledRecord->datasheet;

        abort_unless($datasheet?->datasheet_disk && $datasheet->datasheet_path, 404);
        abort_unless(app(DatasheetPdfPolicy::class)->canInternalPreview($request->user(), $datasheet), 403);

        $disk = Storage::disk($datasheet->datasheet_disk);

        abort_unless($disk->exists($datasheet->datasheet_path), 404);

        $contents = $disk->get($datasheet->datasheet_path);
        $filename = $this->safeFilename($datasheet->datasheet_original_filename ?: 'datasheet.pdf');

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function save(Request $request, string $record, string $workspace, DeviceScanArtifactStorage $storage): RedirectResponse
    {
        $compiledRecord = $this->findRecord($request, $record, $workspace);
        $validated = $request->validate([
            'sections' => ['required', 'array'],
            'sections.*.key' => ['required', 'string', 'max:80'],
            'sections.*.title' => ['required', 'string', 'max:120'],
            'sections.*.rows' => ['array'],
            'sections.*.rows.*.path' => ['nullable', 'string', 'max:500'],
            'sections.*.rows.*.field' => ['required', 'string', 'max:200'],
            'sections.*.rows.*.value' => ['nullable'],
            'sections.*.rows.*.unit' => ['nullable', 'string', 'max:60'],
            'sections.*.rows.*.normalized' => ['nullable'],
            'sections.*.rows.*.page' => ['nullable'],
            'sections.*.rows.*.section' => ['nullable', 'string', 'max:160'],
            'sections.*.rows.*.sourceText' => ['nullable', 'string', 'max:2000'],
        ]);

        $reviewedPayload = $this->reviewedPayload($compiledRecord, $validated['sections']);

        $reviewJson = [
            'compiled_device_record_id' => $compiledRecord->id,
            'compiled_device_record_uuid' => $compiledRecord->uuid,
            'device_datasheet_id' => $compiledRecord->device_datasheet_id,
            'source_type' => $compiledRecord->source_type,
            'workspace' => $workspace,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now()->toIso8601String(),
            'sections' => $validated['sections'],
            'reviewed_payload' => $reviewedPayload,
            'compiled_reference' => [
                'disk' => $compiledRecord->compiled_disk,
                'path' => $compiledRecord->compiled_path,
                'sha256' => $compiledRecord->compiled_sha256,
            ],
        ];
        $artifact = $storage->storeReviewJson($reviewJson, $this->reviewContext($compiledRecord));
        $reviewStatus = $workspace === 'my-library' ? 'pending_review' : 'pending_review';

        $compiledRecord->forceFill([
            ...$this->recordIdentityFromPayload($reviewedPayload),
            'status' => $compiledRecord->status === 'compiled' ? 'review_required' : $compiledRecord->status,
            'review_status' => match ($workspace) {
                'central' => 'librarian_reviewed',
                'my-library' => 'pending_review',
                default => 'publisher_reviewed',
            },
            'metadata' => [
                ...($compiledRecord->metadata ?? []),
                'review_status' => match ($workspace) {
                    'central' => 'librarian_reviewed',
                    'my-library' => 'pending_review',
                    default => 'publisher_reviewed',
                },
                'review_artifact' => [
                    'disk' => $artifact['disk'],
                    'path' => $artifact['path'],
                    'sha256' => $artifact['sha256'],
                    'stored_at' => now()->toIso8601String(),
                ],
            ],
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
        ])->save();

        return back()->with('success', 'Review corrections saved.');
    }

    /**
     * @param  array<int,array<string,mixed>>  $sections
     * @return array<string,mixed>
     */
    private function reviewedPayload(CompiledDeviceRecord $record, array $sections): array
    {
        $payload = $this->readCompiledJson($record) ?? [];

        if (($payload['available'] ?? true) === false) {
            $payload = [];
        }

        foreach ($sections as $section) {
            foreach (($section['rows'] ?? []) as $row) {
                $path = $this->payloadPath((string) ($row['path'] ?? ''));

                if ($path === null || ($row['readonly'] ?? false)) {
                    continue;
                }

                $this->setReviewedValue($payload, $path, $row['value'] ?? null);
            }
        }

        return [
            ...$payload,
            'review_metadata' => [
                ...($payload['review_metadata'] ?? []),
                'reviewed_at' => now()->toIso8601String(),
                'review_source' => 'linewatt_review_screen',
            ],
        ];
    }

    private function payloadPath(string $path): ?string
    {
        if ($path === '' || str_starts_with($path, 'validation.')) {
            return null;
        }

        $replacements = [
            'identity.' => '',
            'electrical.electrical_stc.' => 'electrical_stc.',
            'electrical.dc_input.' => 'dc_input.',
            'electrical.ac_output.' => 'ac_output.',
            'electrical.rated_power_conditions.' => 'rated_power_conditions.',
            'general.mechanical.' => 'mechanical.',
            'general.operating_conditions.' => 'operating_conditions.',
            'general.temperature_characteristics.' => 'temperature_characteristics.',
            'general.certifications.' => 'certifications.',
            'general.central_specific.' => 'central_specific.',
        ];

        foreach ($replacements as $prefix => $replacement) {
            if (str_starts_with($path, $prefix)) {
                return $replacement.Str::after($path, $prefix);
            }
        }

        return $path;
    }

    /**
     * @param  array<string,mixed>  $payload
     */
    private function setReviewedValue(array &$payload, string $path, mixed $value): void
    {
        $existing = Arr::get($payload, $path);

        if (is_array($existing) && (
            array_key_exists('value', $existing)
            || array_key_exists('normalized_value', $existing)
            || array_key_exists('source_text', $existing)
        )) {
            $existing['value'] = $this->coerceReviewedValue($value, $existing['value'] ?? null);

            if (array_key_exists('normalized_value', $existing)) {
                $existing['normalized_value'] = $this->coerceReviewedValue($value, $existing['normalized_value']);
            }

            Arr::set($payload, $path, $existing);

            return;
        }

        Arr::set($payload, $path, $this->coerceReviewedValue($value, $existing));
    }

    private function coerceReviewedValue(mixed $value, mixed $existing): mixed
    {
        if ($value === '') {
            return null;
        }

        if (is_bool($existing)) {
            return in_array(Str::lower((string) $value), ['1', 'true', 'yes', 'y'], true);
        }

        if (is_int($existing)) {
            return is_numeric($value) ? (int) $value : $value;
        }

        if (is_float($existing)) {
            return is_numeric($value) ? (float) $value : $value;
        }

        return $value;
    }

    /**
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     */
    private function recordIdentityFromPayload(array $payload): array
    {
        return array_filter([
            'manufacturer' => $payload['manufacturer'] ?? null,
            'series' => $payload['series'] ?? null,
            'family' => $payload['family'] ?? null,
            'technology' => $payload['technology'] ?? null,
            'model_series' => $payload['model_series'] ?? null,
            'model_name' => $payload['model_name'] ?? null,
            'display_name' => $payload['display_name'] ?? null,
            'power_class_w' => $payload['power_class_w'] ?? null,
            'power_class_kw' => $payload['power_class_kw'] ?? null,
        ], fn ($value): bool => $value !== null && $value !== '');
    }

    private function findRecord(Request $request, string $record, string $workspace): CompiledDeviceRecord
    {
        if ($workspace === 'manufacturer') {
            return $this->findManufacturerRecord($request, $record);
        }

        return CompiledDeviceRecord::query()
            ->with('datasheet')
            ->where(function ($query) use ($record): void {
                if (ctype_digit($record)) {
                    $query->where('id', (int) $record);
                } elseif (Str::isUuid($record)) {
                    $query->where('uuid', $record);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->when($workspace === 'central',
                fn ($query) => $query->whereIn('source_type', ['central_curated', 'partner_submitted', 'tenant_private']),
                fn ($query) => $workspace === 'my-library'
                    ? $query->whereIn('source_type', ['tenant_private', 'pvsyst_import'])
                    : $query->where('source_type', $this->sourceType($workspace))
            )
            ->when($workspace === 'my-library', fn ($query) => $query->where('tenant_id', $request->user()?->id))
            ->when($workspace === 'partner', fn ($query) => $query->where('partner_id', $request->user()?->id))
            ->firstOrFail();
    }

    private function findManufacturerRecord(Request $request, string $record): CompiledDeviceRecord
    {
        $user = $request->user();
        $company = $user?->manufacturerCompany;
        $isPlatformOperator = $user && in_array($user->role, [LineWattRole::SUPER_ADMIN, LineWattRole::ADMIN], true);

        return CompiledDeviceRecord::query()
            ->with('datasheet')
            ->whereIn('source_type', ['central_curated', 'partner_submitted'])
            ->when(! $isPlatformOperator && $company, fn ($query) => $query->whereIn('manufacturer', $this->manufacturerNames($company)))
            ->when(! $isPlatformOperator && ! $company, fn ($query) => $query->whereRaw('1 = 0'))
            ->where(function ($query) use ($record): void {
                if (ctype_digit($record)) {
                    $query->where('id', (int) $record);
                } elseif (Str::isUuid($record)) {
                    $query->where('uuid', $record);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->firstOrFail();
    }

    private function sourceType(string $workspace): string
    {
        return match ($workspace) {
            'my-library' => 'tenant_private',
            'partner' => 'partner_submitted',
            'publisher' => 'central_curated',
            default => 'central_curated',
        };
    }

    /**
     * @return list<string>
     */
    private function manufacturerNames(?ManufacturerCompany $company): array
    {
        if (! $company) {
            return [];
        }

        return collect($company->metadata['manufacturer_aliases'] ?? [])
            ->push($company->name)
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
    private function reviewContext(CompiledDeviceRecord $record): array
    {
        return [
            'source_type' => $record->source_type,
            'device_type' => $record->device_type,
            'manufacturer' => $record->manufacturer,
            'product_name' => $record->datasheet?->product_name ?? $record->series ?? $record->display_name,
            'model_name' => $record->model_name ?: $record->model_series ?: $record->display_name,
            'tenant_uuid' => $record->datasheet?->metadata['tenant_uuid'] ?? null,
            'partner_uuid' => $record->datasheet?->metadata['partner_uuid'] ?? ($record->partner_id ? 'partner-'.$record->partner_id : null),
            'review_uuid' => (string) Str::uuid(),
        ];
    }

    private function commentAndNotify(Request $request, CompiledDeviceRecord $record, string $event, string $action, string $comment, bool $emailSubmitter = false): void
    {
        $this->addReviewComment($request, $record, $action, $comment, null, $record->status);
        $this->notifyTransition($record, $event, match ($event) {
            PublishingEvent::ENGINEERING_RECORD_PUBLISHED => 'Engineering Record published',
            PublishingEvent::ENGINEERING_RECORD_REJECTED => 'Engineering Record rejected',
            default => 'Engineering Record updated',
        }, $comment, $emailSubmitter);
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

    private function notifyTransition(CompiledDeviceRecord $record, string $event, string $title, ?string $body, bool $emailSubmitter = false): void
    {
        $activity = app(ActivityLogger::class)->log($event, auth()->user(), $record);
        app(NotificationManager::class)->notifySubmitter(
            $record,
            $event,
            $title,
            $body,
            route('records.show', ['record' => $record->uuid ?: $record->id]),
            $activity,
            $emailSubmitter,
        );
    }
}
