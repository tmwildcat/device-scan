<?php

namespace App\Http\Controllers\LineWatt;

use App\DeviceScan\Storage\DeviceScanArtifactStorage;
use App\Http\Controllers\Controller;
use App\Jobs\CompileDeviceDatasheetJob;
use App\LineWatt\Activity\ActivityLogger;
use App\LineWatt\Manufacturers\ManufacturerNormalizer;
use App\LineWatt\Notifications\NotificationManager;
use App\LineWatt\Publishing\PublishingEvent;
use App\LineWatt\Uploads\UploadSecurityService;
use App\Models\DeviceDatasheet;
use App\Models\ManufacturerCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UploadController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route('my-library.uploads.new', [
            'device_type' => $request->query('device_type', 'module'),
        ]);
    }

    public function createSubscriber(Request $request): Response
    {
        return $this->renderUpload($request, 'my-library');
    }

    public function storeSubscriber(
        Request $request,
        UploadSecurityService $security,
        DeviceScanArtifactStorage $storage,
    ): RedirectResponse {
        return $this->store($request, $security, $storage, 'my-library');
    }

    public function manufacturers(Request $request, ManufacturerNormalizer $normalizer): JsonResponse
    {
        return response()->json($normalizer->suggestions(
            (string) $request->query('q', ''),
            in_array($request->query('device_type'), ['module', 'inverter'], true) ? (string) $request->query('device_type') : null
        ));
    }

    public function createCentral(Request $request): Response
    {
        return $this->renderUpload($request, 'central');
    }

    public function storeCentral(
        Request $request,
        UploadSecurityService $security,
        DeviceScanArtifactStorage $storage,
    ): RedirectResponse {
        return $this->store($request, $security, $storage, 'central');
    }

    public function createPublisher(Request $request): Response
    {
        return $this->renderUpload($request, 'publisher');
    }

    public function storePublisher(
        Request $request,
        UploadSecurityService $security,
        DeviceScanArtifactStorage $storage,
    ): RedirectResponse {
        return $this->store($request, $security, $storage, 'publisher');
    }

    public function createPartner(Request $request): Response
    {
        return $this->renderUpload($request, 'partner');
    }

    public function storePartner(
        Request $request,
        UploadSecurityService $security,
        DeviceScanArtifactStorage $storage,
    ): RedirectResponse {
        return $this->store($request, $security, $storage, 'partner');
    }

    private function renderUpload(Request $request, string $workspace): Response
    {
        $deviceType = $request->string('device_type')->toString();

        if (! in_array($deviceType, ['module', 'inverter'], true)) {
            $deviceType = 'module';
        }

        $labels = $this->workspaceLabels($workspace);

        return Inertia::render('LineWatt/Upload', [
            'deviceType' => $deviceType,
            'workspace' => $workspace,
            'workspaceName' => $labels['workspace_name'],
            'destinationLabel' => $labels['destination_label'],
            'postUrl' => $labels['post_url'],
            'pvsystPostUrl' => $workspace === 'my-library' ? route('my-library.pvsyst-import.store') : null,
            'backUrl' => $labels['back_url'],
            'maxPdfSizeMb' => (int) config('linewatt-library.upload.max_pdf_size_mb', 25),
            'malwareScanEnabled' => (bool) config('linewatt-library.upload.malware_scan.enabled', false),
            'lockedManufacturer' => $workspace === 'partner' ? $request->user()?->manufacturerCompany?->name : null,
        ]);
    }

    private function store(
        Request $request,
        UploadSecurityService $security,
        DeviceScanArtifactStorage $storage,
        string $workspace,
    ): RedirectResponse {
        $validated = $request->validate([
            'device_type' => ['required', 'string', 'in:module,inverter'],
            'manufacturer' => ['required', 'string', 'max:120'],
            'product_name' => ['nullable', 'string', 'max:180'],
            'source_url' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'datasheet' => [
                'required',
                'file',
                'max:'.((int) config('linewatt-library.upload.max_pdf_size_mb', 25) * 1024),
            ],
        ]);

        $file = $request->file('datasheet');
        $sourceType = $this->sourceType($workspace);
        $scope = [
            'source_type' => $sourceType,
            'device_type' => $validated['device_type'],
            ...$this->ownershipIds($request, $workspace),
        ];
        $stagedPath = $this->stageUpload($file);

        try {
            $securityResult = $security->inspect($stagedPath, $file, $scope);

            if (! $securityResult->passed) {
                Log::warning('LineWatt upload security rejected a datasheet.', [
                    'errors' => $securityResult->errors,
                    'warnings' => $securityResult->warnings,
                    'metadata' => $securityResult->metadata,
                    'source_type' => $sourceType,
                    'user_id' => $request->user()?->id,
                ]);

                $this->notifySecurityRejectedUpload($request, $workspace, $sourceType, $validated, $securityResult->errors);

                return back()
                    ->withErrors(['datasheet' => $this->safeUploadError($securityResult->errors)])
                    ->withInput($request->except('datasheet'));
            }

            $manufacturerResult = $this->manufacturerForUpload($request, $workspace, $validated['manufacturer'] ?? null);
            $manufacturer = $manufacturerResult['name'];
            $productName = $this->cleanText($validated['product_name'] ?? null)
                ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $pathContext = [
                'source_type' => $sourceType,
                'device_type' => $validated['device_type'],
                'manufacturer' => $manufacturer,
                'product_name' => $productName,
                'datasheet_uuid' => (string) Str::uuid(),
                'extension' => 'pdf',
                ...$this->pathScope($request, $workspace),
            ];
            $artifact = $storage->storeDatasheet($stagedPath, $pathContext);

            $datasheet = DeviceDatasheet::create([
                'source_type' => $sourceType,
                ...$this->ownershipIds($request, $workspace),
                'device_type' => $validated['device_type'],
                'manufacturer' => $manufacturer,
                'product_name' => $productName,
                'status' => 'security_checked',
                'review_status' => 'not_reviewed',
                'datasheet_disk' => $artifact['disk'],
                'datasheet_path' => $artifact['path'],
                'datasheet_original_filename' => $file->getClientOriginalName(),
                'datasheet_mime_type' => $securityResult->mimeType ?? $artifact['mime_type'] ?? 'application/pdf',
                'datasheet_size_bytes' => $securityResult->sizeBytes ?? $artifact['size_bytes'],
                'datasheet_sha256' => $securityResult->sha256 ?? $artifact['sha256'],
                ...$this->pdfPolicyForUpload($workspace, $validated['source_url'] ?? null),
                'metadata' => [
                    'upload_workspace' => $workspace,
                    'uploaded_by' => $request->user()?->id,
                    'upload_notes' => $this->cleanText($validated['notes'] ?? null),
                    'manufacturer_normalization' => $manufacturerResult,
                    'manufacturer_company_id' => $request->user()?->manufacturer_company_id,
                    'security' => [
                        'warnings' => $securityResult->warnings,
                        'metadata' => $securityResult->metadata,
                    ],
                    ...$this->pathScope($request, $workspace),
                ],
            ]);

            try {
                CompileDeviceDatasheetJob::dispatchSync($datasheet->id, $validated['device_type']);
            } catch (\Throwable $exception) {
                Log::error('LineWatt datasheet compile failed after upload.', [
                    'device_datasheet_id' => $datasheet->id,
                    'error' => $exception->getMessage(),
                ]);

                return redirect($this->workspaceLabels($workspace)['back_url'])
                    ->with('error', 'Upload passed security checks, but compilation failed. The datasheet is available for retry.');
            }

            return $this->redirectAfterCompile($datasheet, $workspace);
        } finally {
            if (is_file($stagedPath)) {
                @unlink($stagedPath);
            }
        }
    }

    /**
     * @return array{workspace_name:string,destination_label:string,post_url:string,back_url:string}
     */
    private function workspaceLabels(string $workspace): array
    {
        return match ($workspace) {
            'central' => [
                'workspace_name' => 'Central Engineering Workspace',
                'destination_label' => 'Central curated intake',
                'post_url' => route('central-library.uploads.store'),
                'back_url' => route('central-library'),
            ],
            'publisher' => [
                'workspace_name' => 'Library Publisher',
                'destination_label' => 'Publisher review queue',
                'post_url' => route('publisher.uploads.store'),
                'back_url' => route('publisher'),
            ],
            'partner' => [
                'workspace_name' => 'Partner Portal',
                'destination_label' => 'Partner submission queue',
                'post_url' => route('partner.submissions.store'),
                'back_url' => route('partner'),
            ],
            default => [
                'workspace_name' => 'My Private Datasets',
                'destination_label' => 'Private dataset intake',
                'post_url' => route('my-library.uploads.store'),
                'back_url' => route('my-library'),
            ],
        };
    }

    /**
     * @param  array<int,string>  $errors
     * @param  array<string,mixed>  $validated
     */
    private function notifySecurityRejectedUpload(Request $request, string $workspace, string $sourceType, array $validated, array $errors): void
    {
        $activity = app(ActivityLogger::class)->log(PublishingEvent::MALWARE_BLOCKED_UPLOAD, $request->user(), null, [
            'workspace' => $workspace,
            'source_type' => $sourceType,
            'device_type' => $validated['device_type'] ?? null,
            'manufacturer' => $validated['manufacturer'] ?? null,
            'errors' => $errors,
        ]);

        app(NotificationManager::class)->notifyLibrarians(
            PublishingEvent::MALWARE_BLOCKED_UPLOAD,
            'Upload blocked by security checks',
            'A datasheet upload was rejected before permanent storage and needs operator visibility.',
            route('admin.library.placeholder', ['section' => 'operations', 'page' => 'malware-scan-logs']),
            $activity,
        );
    }

    private function sourceType(string $workspace): string
    {
        return match ($workspace) {
            'central' => 'central_curated',
            'publisher' => 'central_curated',
            'partner' => 'partner_submitted',
            default => 'tenant_private',
        };
    }

    /**
     * @return array<string,mixed>
     */
    private function pdfPolicyForUpload(string $workspace, ?string $sourceUrl): array
    {
        $sourceUrl = $this->cleanText($sourceUrl);
        $sourceDomain = $sourceUrl ? parse_url($sourceUrl, PHP_URL_HOST) : null;

        if ($workspace === 'partner') {
            return [
                'pdf_access_mode' => 'partner_supplied',
                'source_url' => $sourceUrl,
                'source_domain' => is_string($sourceDomain) ? $sourceDomain : null,
                'permission_status' => 'partner_authorized',
                'can_public_download' => true,
                'can_public_preview' => true,
                'can_internal_preview' => true,
                'can_private_download' => true,
            ];
        }

        if ($workspace === 'my-library') {
            return [
                'pdf_access_mode' => 'user_private',
                'source_url' => $sourceUrl,
                'source_domain' => is_string($sourceDomain) ? $sourceDomain : null,
                'permission_status' => 'restricted',
                'can_public_download' => false,
                'can_public_preview' => false,
                'can_internal_preview' => true,
                'can_private_download' => true,
            ];
        }

        return [
            'pdf_access_mode' => $sourceUrl ? 'external_link_only' : 'internal_only',
            'source_url' => $sourceUrl,
            'source_domain' => is_string($sourceDomain) ? $sourceDomain : null,
            'permission_status' => 'unknown',
            'can_public_download' => false,
            'can_public_preview' => false,
            'can_internal_preview' => true,
            'can_private_download' => true,
        ];
    }

    /**
     * @return array{name:?string,matched:bool,source:string,variants:list<string>}
     */
    private function manufacturerForUpload(Request $request, string $workspace, ?string $submitted): array
    {
        if ($workspace === 'partner') {
            $company = $request->user()?->manufacturerCompany;

            abort_unless($company instanceof ManufacturerCompany, 422, 'Manufacturer account is not linked to a manufacturer company.');

            return [
                'name' => $company->name,
                'matched' => true,
                'source' => 'manufacturer_account',
                'variants' => array_values(array_filter([$submitted, $company->name])),
            ];
        }

        return app(ManufacturerNormalizer::class)->normalize($submitted);
    }

    /**
     * @return array<string,int|null>
     */
    private function ownershipIds(Request $request, string $workspace): array
    {
        return match ($workspace) {
            'partner' => ['partner_id' => $request->user()?->id],
            'my-library' => ['tenant_id' => $request->user()?->id],
            default => [],
        };
    }

    /**
     * @return array<string,string|null>
     */
    private function pathScope(Request $request, string $workspace): array
    {
        return match ($workspace) {
            'partner' => ['partner_uuid' => 'partner-'.$request->user()?->id],
            'my-library' => ['tenant_uuid' => 'tenant-'.$request->user()?->id],
            default => [],
        };
    }

    private function stageUpload($file): string
    {
        $directory = storage_path('app/private/linewatt-upload-staging');

        if (! is_dir($directory)) {
            mkdir($directory, 0750, true);
        }

        $path = $directory.'/'.(string) Str::uuid().'.upload';
        $sourcePath = $file->getRealPath() ?: $file->getPathname();

        if (! is_string($sourcePath) || ! is_readable($sourcePath)) {
            throw new \RuntimeException('Uploaded file could not be staged for security checks.');
        }

        copy($sourcePath, $path);

        return $path;
    }

    private function safeUploadError(array $errors): string
    {
        return match ($errors[0] ?? null) {
            'file_too_large' => 'The PDF is larger than the configured upload limit.',
            'duplicate_upload' => 'This datasheet has already been uploaded in this workspace.',
            'encrypted_pdf_not_supported' => 'Password-protected or encrypted PDFs are not supported.',
            'malware_scan_failed' => 'The datasheet did not pass the upload security scan.',
            default => 'The uploaded file must be a valid PDF datasheet.',
        };
    }

    private function cleanText(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function redirectAfterCompile(DeviceDatasheet $datasheet, string $workspace): RedirectResponse
    {
        $records = $datasheet->compiledRecords()->latest()->get();

        if ($records->count() === 1) {
            $record = $records->first();

            return redirect($this->reviewRoute($record, $workspace))
                ->with('success', 'Upload compiled. Review the extracted Engineering Record.');
        }

        return redirect()->route('device-scan.uploads.compiled-records', ['datasheet' => $datasheet])
            ->with('success', 'Upload compiled into multiple Engineering Records. Choose a record to review.');
    }

    private function reviewRoute($record, string $workspace): string
    {
        return match ($workspace) {
            'central' => route('central-library.review', ['record' => $record->uuid ?: $record->id]),
            'publisher' => route('publisher.review.show', ['record' => $record->uuid ?: $record->id]),
            'partner' => route('partner.submissions.review', ['record' => $record->uuid ?: $record->id]),
            default => route('my-library.records.review', ['record' => $record->uuid ?: $record->id]),
        };
    }
}
