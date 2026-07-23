<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LineWatt\Concerns\BuildsEngineeringRecordPayloads;
use App\LineWatt\Access\LineWattRole;
use App\LineWatt\Pdf\DatasheetPdfPolicy;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\ManufacturerCompany;
use App\Models\ManufacturerSupportingDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ManufacturerProductController extends Controller implements HasMiddleware
{
    use BuildsEngineeringRecordPayloads;

    public static function middleware(): array
    {
        return [new Middleware('legal.acceptance:manufacturer.portal.access')];
    }

    public function companyProfile(Request $request): Response
    {
        $company = $request->user()?->manufacturerCompany;
        $companyPayload = $this->companyPayload($request);
        $metadata = $company?->metadata ?? [];

        $datasheetQuery = $this->visibleDatasheetQuery($request);
        $recordQuery = $this->visibleRecordQuery($request);

        return Inertia::render('LineWatt/ManufacturerCompanyProfile', [
            'company' => $companyPayload,
            'profile' => [
                'identity' => [
                    'Company name' => $companyPayload['name'],
                    'Legal name' => $metadata['legal_name'] ?? $companyPayload['name'],
                    'Brand name' => $metadata['brand_name'] ?? $companyPayload['name'],
                    'Slug' => $company?->slug ?? 'pending',
                    'Headquarters country' => $metadata['headquarters_country'] ?? 'Pending',
                    'Website' => $metadata['website'] ?? 'Pending',
                ],
                'businessDescription' => [
                    'Short description' => $metadata['short_description'] ?? 'Manufacturer profile description is ready to be added.',
                    'Primary technologies' => implode(', ', $metadata['primary_technologies'] ?? ['Modules', 'Inverters']),
                    'Primary markets' => implode(', ', $metadata['primary_markets'] ?? ['Pending']),
                    'Bankability notes' => $metadata['bankability_notes'] ?? 'Pending',
                ],
                'factorySummary' => [
                    'Known factories' => (string) $this->factoryLocationCount($company),
                    'Primary manufacturing country' => $metadata['primary_manufacturing_country'] ?? 'Pending',
                    'Factory certification status' => $metadata['factory_certification_status'] ?? 'Pending',
                    'href' => '/admin/manufacturer/company/factories',
                ],
                'distributionSummary' => [
                    'Distribution coverage' => $metadata['distribution_coverage'] ?? $this->distributionCountryCount($company).' countries',
                    'Priority regions' => implode(', ', $metadata['priority_regions'] ?? ['South Asia', 'North America', 'Europe']),
                    'Channel model' => $metadata['channel_model'] ?? 'Distributor / Direct',
                    'href' => '/admin/manufacturer/company/distribution-countries',
                ],
                'contacts' => [
                    ['Type' => 'Sales', 'Name' => $metadata['sales_contact_name'] ?? 'Pending', 'Email' => $metadata['sales_contact_email'] ?? 'Pending', 'Phone' => $metadata['sales_contact_phone'] ?? 'Pending'],
                    ['Type' => 'Technical', 'Name' => $metadata['technical_contact_name'] ?? 'Pending', 'Email' => $metadata['technical_contact_email'] ?? 'Pending', 'Phone' => $metadata['technical_contact_phone'] ?? 'Pending'],
                    ['Type' => 'Warranty / Service', 'Name' => $metadata['service_contact_name'] ?? 'Pending', 'Email' => $metadata['service_contact_email'] ?? 'Pending', 'Phone' => $metadata['service_contact_phone'] ?? 'Pending'],
                ],
                'libraryPresence' => [
                    'public_url' => route('manufacturers.show', ['manufacturer' => $company?->slug ?? str($companyPayload['name'])->slug()->toString()]),
                    'qr_png_href' => route('admin.manufacturer.company.profile.qr', ['format' => 'png']),
                    'qr_svg_href' => route('admin.manufacturer.company.profile.qr', ['format' => 'svg']),
                    'profile_status' => $metadata['profile_status'] ?? 'Draft',
                    'visibility' => $metadata['visibility'] ?? 'Private preview',
                    'last_published' => $metadata['last_published_at'] ?? 'Not published',
                    'seo_status' => $metadata['seo_status'] ?? 'Future SEO controls placeholder',
                ],
                'verification' => [
                    'Verification status' => $metadata['verification_status'] ?? 'Pending verification',
                    'Verified by' => $metadata['verified_by'] ?? 'LineWatt Library',
                    'Last reviewed' => $metadata['last_reviewed_at'] ?? 'Pending',
                    'Notes' => $metadata['verification_notes'] ?? 'Company profile review workflow is prepared for a later milestone.',
                ],
            ],
            'logo' => [
                'preview_href' => ($company?->metadata['logo_path'] ?? null) ? route('admin.manufacturer.company.profile.logo') : null,
                'original_filename' => $company?->metadata['logo_original_filename'] ?? null,
                'updated_at' => $company?->metadata['logo_updated_at'] ?? null,
                'upload_href' => route('admin.manufacturer.company.profile.logo.store'),
                'remove_href' => route('admin.manufacturer.company.profile.logo.destroy'),
            ],
            'visitorPreview' => [
                'company_name' => $companyPayload['name'],
                'logo_href' => ($company?->metadata['logo_path'] ?? null) ? route('admin.manufacturer.company.profile.logo') : null,
                'description' => $metadata['short_description'] ?? 'Manufacturer profile description is ready to be added.',
                'technologies' => $metadata['primary_technologies'] ?? ['Modules', 'Inverters'],
                'product_categories' => $this->productCategories($request),
                'factory_summary' => [
                    'known_factories' => $this->factoryLocationCount($company),
                    'primary_country' => $metadata['primary_manufacturing_country'] ?? 'Pending',
                    'certification_status' => $metadata['factory_certification_status'] ?? 'Pending',
                ],
                'distribution_summary' => [
                    'countries' => $this->distributionCountryCount($company),
                    'priority_regions' => $metadata['priority_regions'] ?? ['South Asia', 'North America', 'Europe'],
                    'channel_model' => $metadata['channel_model'] ?? 'Distributor / Direct',
                ],
                'latest_datasheets' => $this->latestDatasheetRows($request),
                'company_documents' => array_slice($this->companySupportingDocumentRows($request), 0, 5),
                'contacts' => $this->previewContactRows($request, $metadata),
            ],
            'companyDocuments' => $this->companySupportingDocumentRows($request),
        ]);
    }

    public function datasheets(Request $request): Response
    {
        return Inertia::render('LineWatt/ManufacturerDatasheets', [
            'company' => $this->companyPayload($request),
            'datasheets' => $this->visibleDatasheetQuery($request)
                ->latest()
                ->paginate(15)
                ->through(fn (DeviceDatasheet $datasheet): array => $this->datasheetRow($datasheet)),
        ]);
    }

    public function showDatasheet(Request $request, DeviceDatasheet $datasheet): Response
    {
        $visible = $this->visibleDatasheetQuery($request)->whereKey($datasheet->id)->exists();
        abort_unless($visible, 404);

        $datasheet->load(['compiledRecords' => fn ($query) => $query->latest()]);
        $records = $datasheet->compiledRecords;

        return Inertia::render('LineWatt/ManufacturerDatasheetDetail', [
            'company' => $this->companyPayload($request),
            'datasheet' => $this->datasheetRow($datasheet, includeDetail: true),
            'models' => $records->map(fn (CompiledDeviceRecord $record): array => $this->modelRow($record, $datasheet))->values()->all(),
            'structuredEngineeringData' => $records->map(fn (CompiledDeviceRecord $record): array => $this->structuredEngineeringDataRow($record, $datasheet))->values()->all(),
            'powerSearchCategories' => $this->powerSearchCategories(),
            'manufacturingLocations' => $this->manufacturingLocations($request),
            'supportingDocuments' => $this->datasheetSpecificSupportingDocumentRows($datasheet),
            'history' => $this->historyRows($datasheet, $records),
        ]);
    }

    public function models(Request $request): Response
    {
        $records = $this->visibleRecordQuery($request)
            ->with('datasheet')
            ->latest()
            ->paginate(20)
            ->through(fn (CompiledDeviceRecord $record): array => $this->modelRow($record, $record->datasheet));

        return Inertia::render('LineWatt/ManufacturerModels', [
            'company' => $this->companyPayload($request),
            'models' => $records,
        ]);
    }

    public function structuredEngineeringData(Request $request): Response
    {
        $records = $this->visibleRecordQuery($request)
            ->with('datasheet')
            ->when($request->string('status')->isNotEmpty(), fn (Builder $query) => $query->where('status', $request->string('status')->toString()))
            ->latest()
            ->paginate(20)
            ->through(fn (CompiledDeviceRecord $record): array => $this->structuredEngineeringDataRow($record, $record->datasheet));

        return Inertia::render('LineWatt/ManufacturerStructuredEngineeringData', [
            'company' => $this->companyPayload($request),
            'records' => $records,
        ]);
    }

    public function supportingDocuments(Request $request): Response
    {
        $datasheetDocuments = $this->visibleDatasheetQuery($request)
            ->latest()
            ->limit(12)
            ->get()
            ->flatMap(fn (DeviceDatasheet $datasheet): array => $this->datasheetSpecificSupportingDocumentRows($datasheet))
            ->values()
            ->all();

        return Inertia::render('LineWatt/ManufacturerSupportingDocuments', [
            'company' => $this->companyPayload($request),
            'companyDocuments' => $this->companySupportingDocumentRows($request),
            'datasheetDocuments' => $datasheetDocuments,
        ]);
    }

    public function factoryLocations(Request $request): Response
    {
        return Inertia::render('LineWatt/ManufacturerSimpleAdminPage', [
            'company' => $this->companyPayload($request),
            'title' => 'Factory Locations',
            'description' => 'Company-level manufacturing locations that can later be linked to datasheets, model families or production programs.',
            'columns' => ['Factory name', 'Country', 'State', 'City', 'Product types', 'Production capacity', 'Certifications', 'Status'],
            'rows' => $this->manufacturingLocations($request),
            'emptyState' => 'Factory locations are ready to be added.',
        ]);
    }

    public function distributionCountries(Request $request): Response
    {
        return Inertia::render('LineWatt/ManufacturerSimpleAdminPage', [
            'company' => $this->companyPayload($request),
            'title' => 'Distribution Countries',
            'description' => 'Company-level availability, region and distribution mode placeholders.',
            'columns' => ['Country', 'Region', 'Availability status', 'Distributor / Direct', 'Notes'],
            'rows' => [
                ['Country' => 'India', 'Region' => 'South Asia', 'Availability status' => 'Available', 'Distributor / Direct' => 'Distributor / Direct', 'Notes' => 'Demo placeholder'],
                ['Country' => 'United States', 'Region' => 'North America', 'Availability status' => 'Planned', 'Distributor / Direct' => 'Distributor', 'Notes' => 'Demo placeholder'],
            ],
            'emptyState' => 'Distribution countries are ready to be added.',
        ]);
    }

    public function countryContacts(Request $request): Response
    {
        return Inertia::render('LineWatt/ManufacturerSimpleAdminPage', [
            'company' => $this->companyPayload($request),
            'title' => 'Country Contacts',
            'description' => 'Sales, technical support, warranty and service contacts by country.',
            'columns' => ['Country', 'Sales contact', 'Technical support', 'Warranty contact', 'Service contact', 'Email', 'Phone', 'Website'],
            'rows' => [
                ['Country' => 'India', 'Sales contact' => 'Regional Sales', 'Technical support' => 'Technical Desk', 'Warranty contact' => 'Warranty Desk', 'Service contact' => 'Service Desk', 'Email' => 'contact@example.com', 'Phone' => 'Pending', 'Website' => 'Pending'],
            ],
            'emptyState' => 'Country contacts are ready to be added.',
        ]);
    }

    public function websiteIntegration(Request $request): Response
    {
        return Inertia::render('LineWatt/ManufacturerWebsiteIntegration', [
            'company' => $this->companyPayload($request),
        ]);
    }

    public function sourcePdf(Request $request, DeviceDatasheet $datasheet): SymfonyResponse
    {
        $visible = $this->visibleDatasheetQuery($request)->whereKey($datasheet->id)->exists();
        abort_unless($visible, 404);
        abort_unless(app(DatasheetPdfPolicy::class)->canInternalPreview($request->user(), $datasheet), 403);
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

    /**
     * @return Builder<DeviceDatasheet>
     */
    private function visibleDatasheetQuery(Request $request): Builder
    {
        $user = $request->user();
        $company = $user?->manufacturerCompany;
        $isPlatformOperator = $user && in_array($user->role, [LineWattRole::SUPER_ADMIN, LineWattRole::ADMIN], true);

        return DeviceDatasheet::query()
            ->withCount('compiledRecords')
            ->whereIn('source_type', ['central_curated', 'partner_submitted'])
            ->whereIn('status', ['uploaded', 'compiled', 'review_required', 'approved', 'published', 'discontinued', 'replaced'])
            ->when(! $isPlatformOperator && $company, fn (Builder $query) => $query->whereIn('manufacturer', $this->manufacturerNames($company)))
            ->when(! $isPlatformOperator && ! $company, fn (Builder $query) => $query->whereRaw('1 = 0'));
    }

    /**
     * @return Builder<CompiledDeviceRecord>
     */
    private function visibleRecordQuery(Request $request): Builder
    {
        $user = $request->user();
        $company = $user?->manufacturerCompany;
        $isPlatformOperator = $user && in_array($user->role, [LineWattRole::SUPER_ADMIN, LineWattRole::ADMIN], true);

        return CompiledDeviceRecord::query()
            ->whereIn('source_type', ['central_curated', 'partner_submitted'])
            ->whereIn('status', ['compiled', 'review_required', 'approved', 'published', 'discontinued', 'replaced'])
            ->when(! $isPlatformOperator && $company, fn (Builder $query) => $query->whereIn('manufacturer', $this->manufacturerNames($company)))
            ->when(! $isPlatformOperator && ! $company, fn (Builder $query) => $query->whereRaw('1 = 0'));
    }

    /**
     * @return array<string,mixed>
     */
    private function datasheetRow(DeviceDatasheet $datasheet, bool $includeDetail = false): array
    {
        $row = [
            'id' => $datasheet->id,
            'uuid' => $datasheet->uuid,
            'title' => $datasheet->product_name ?: $datasheet->datasheet_original_filename ?: 'Datasheet',
            'filename' => $datasheet->datasheet_original_filename ?: 'datasheet.pdf',
            'family_series' => $datasheet->series ?: $datasheet->product_name ?: 'Pending',
            'models_count' => $datasheet->compiled_records_count ?? $datasheet->compiledRecords()->count(),
            'revision' => $datasheet->metadata['revision'] ?? 'v1',
            'language' => $datasheet->metadata['language'] ?? 'English',
            'publication_date' => $datasheet->metadata['publication_date'] ?? null,
            'effective_date' => $datasheet->metadata['effective_date'] ?? null,
            'supersedes_revision' => $datasheet->metadata['supersedes_revision'] ?? null,
            'status' => $datasheet->status,
            'uploaded' => $datasheet->created_at?->toDateString(),
            'archival_status' => in_array($datasheet->status, ['discontinued', 'replaced'], true) ? 'Archived' : 'Current',
            'notes' => $datasheet->metadata['notes'] ?? null,
            'preview_href' => route('admin.manufacturer.datasheets.pdf', ['datasheet' => $datasheet->id]),
            'show_href' => route('admin.manufacturer.datasheets.show', ['datasheet' => $datasheet->id]),
            'review_href' => route('admin.manufacturer.datasheets.review', ['datasheet' => $datasheet->id]),
            'replace_href' => route('partner.submissions.new'),
            'history_href' => route('admin.manufacturer.datasheets.show', ['datasheet' => $datasheet->id, 'tab' => 'History']),
            'supporting_documents_href' => route('admin.manufacturer.datasheets.show', ['datasheet' => $datasheet->id, 'tab' => 'Supporting Documents']),
        ];

        if ($includeDetail) {
            $row['storage'] = [
                'disk' => $datasheet->datasheet_disk,
                'path' => $datasheet->datasheet_path,
                'sha256' => $datasheet->datasheet_sha256,
                'size_bytes' => $datasheet->datasheet_size_bytes,
            ];
        }

        return $row;
    }

    /**
     * @return array<string,mixed>
     */
    private function modelRow(CompiledDeviceRecord $record, ?DeviceDatasheet $datasheet): array
    {
        return [
            'id' => $record->id,
            'uuid' => $record->uuid,
            'model' => $record->display_name ?: $record->model_name ?: $record->model_series ?: 'Model pending',
            'datasheet' => $datasheet?->datasheet_original_filename ?: $datasheet?->product_name ?: 'Datasheet pending',
            'datasheet_href' => $datasheet ? route('admin.manufacturer.datasheets.show', ['datasheet' => $datasheet->id]) : null,
            'family_series' => $record->series ?: $record->model_series ?: $datasheet?->series ?: 'Pending',
            'power' => $this->powerLabel($record),
            'technology' => $record->technology ?: 'Pending',
            'status' => $record->status,
            'structured_data_status' => $record->validation_status ?: 'compiled',
            'open_href' => route('records.show', ['record' => $record->uuid ?: $record->id]),
            'review_href' => route('admin.manufacturer.engineering-data.review', ['record' => $record->uuid ?: $record->id]),
            'compare_href' => route('compare', ['records' => $record->id]),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function structuredEngineeringDataRow(CompiledDeviceRecord $record, ?DeviceDatasheet $datasheet): array
    {
        return [
            ...$this->recordSummary($record),
            'model' => $record->display_name ?: $record->model_name ?: $record->model_series ?: 'Model pending',
            'datasheet' => $datasheet?->datasheet_original_filename ?: $datasheet?->product_name ?: 'Datasheet pending',
            'datasheet_href' => $datasheet ? route('admin.manufacturer.datasheets.show', ['datasheet' => $datasheet->id]) : null,
            'version' => ($record->metadata['review_artifact'] ?? null) ? 'Reviewed' : 'Compiled',
            'updated' => $record->updated_at?->toDateString(),
            'open_href' => route('records.show', ['record' => $record->uuid ?: $record->id]),
            'review_href' => route('admin.manufacturer.engineering-data.review', ['record' => $record->uuid ?: $record->id]),
            'history_href' => $datasheet ? route('admin.manufacturer.datasheets.show', ['datasheet' => $datasheet->id, 'tab' => 'History']) : '#',
        ];
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function datasheetSpecificSupportingDocumentRows(DeviceDatasheet $datasheet): array
    {
        $storedRows = $this->storedSupportingDocumentRows(
            ManufacturerSupportingDocument::SCOPE_DATASHEET,
            fn (Builder $query): Builder => $query->where('device_datasheet_id', $datasheet->id)
        );

        if ($storedRows !== []) {
            return $storedRows;
        }

        return [
            [
                'Scope' => 'Datasheet-specific',
                'Document title' => $datasheet->datasheet_original_filename ?: 'Source datasheet',
                'Category' => 'Datasheet',
                'Related datasheet / family / model' => $datasheet->product_name ?: $datasheet->series ?: 'Current datasheet',
                'Revision' => $datasheet->metadata['revision'] ?? 'v1',
                'Language' => $datasheet->metadata['language'] ?? 'English',
                'Status' => $datasheet->status,
                'Uploaded' => $datasheet->created_at?->toDateString() ?? 'Pending',
                'Actions' => 'Preview / Replace / History',
            ],
        ];
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function companySupportingDocumentRows(Request $request): array
    {
        $company = $request->user()?->manufacturerCompany;
        $storedRows = $this->storedSupportingDocumentRows(
            ManufacturerSupportingDocument::SCOPE_COMPANY,
            fn (Builder $query): Builder => $company
                ? $query->where('manufacturer_company_id', $company->id)
                : $query->whereNull('manufacturer_company_id')
        );

        if ($storedRows !== []) {
            return $storedRows;
        }

        $companyName = $company?->name ?? 'Manufacturer';

        return [
            [
                'Scope' => 'Company-wide',
                'Document title' => "{$companyName} company profile",
                'Category' => 'Company profile brochure',
                'Related datasheet / family / model' => $companyName,
                'Revision' => 'Pending',
                'Language' => 'English',
                'Status' => 'Ready to upload',
                'Uploaded' => 'Pending',
                'Actions' => 'Upload / History',
            ],
            [
                'Scope' => 'Company-wide',
                'Document title' => 'ISO and corporate certifications',
                'Category' => 'Corporate certification',
                'Related datasheet / family / model' => $companyName,
                'Revision' => 'Pending',
                'Language' => 'English',
                'Status' => 'Ready to upload',
                'Uploaded' => 'Pending',
                'Actions' => 'Upload / History',
            ],
            [
                'Scope' => 'Company-wide',
                'Document title' => 'Warranty policy',
                'Category' => 'Warranty policy',
                'Related datasheet / family / model' => $companyName,
                'Revision' => 'Pending',
                'Language' => 'English',
                'Status' => 'Ready to upload',
                'Uploaded' => 'Pending',
                'Actions' => 'Upload / History',
            ],
        ];
    }

    /**
     * @param  callable(Builder<ManufacturerSupportingDocument>): Builder<ManufacturerSupportingDocument>  $scopeQuery
     * @return array<int,array<string,string>>
     */
    private function storedSupportingDocumentRows(string $scope, callable $scopeQuery): array
    {
        if (! Schema::hasTable('manufacturer_supporting_documents')) {
            return [];
        }

        return $scopeQuery(
            ManufacturerSupportingDocument::query()->where('supporting_document_scope', $scope)
        )
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (ManufacturerSupportingDocument $document): array => [
                'Scope' => $document->supporting_document_scope === ManufacturerSupportingDocument::SCOPE_COMPANY ? 'Company-wide' : 'Datasheet-specific',
                'Document title' => $document->title ?: $document->document_original_filename ?: 'Supporting document',
                'Category' => $document->category ?: 'Supporting document',
                'Related datasheet / family / model' => $document->related_label ?: $document->model_name ?: 'Pending',
                'Revision' => $document->revision ?: 'Pending',
                'Language' => $document->language ?: 'Pending',
                'Status' => $document->status ?: 'Pending',
                'Uploaded' => $document->created_at?->toDateString() ?? 'Pending',
                'Actions' => 'Open / Replace / History',
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function manufacturingLocations(Request $request): array
    {
        $company = $request->user()?->manufacturerCompany;

        return [
            [
                'Factory name' => ($company?->name ?? 'Manufacturer').' Primary Plant',
                'Country' => 'India',
                'State' => 'Pending',
                'City' => 'Pending',
                'Product types' => 'Modules / Inverters',
                'Production capacity' => 'Pending',
                'Certifications' => 'ISO / IEC placeholders',
                'Status' => 'Active',
            ],
        ];
    }

    /**
     * @return array<int,array{category:string,options:string[]}>
     */
    private function powerSearchCategories(): array
    {
        return [
            ['category' => 'Region', 'options' => ['South Asia', 'North America', 'Europe']],
            ['category' => 'Country-specific Programs', 'options' => ['Make in India', 'PM-KUSUM Approved', 'ALMM Listed']],
            ['category' => 'Applications', 'options' => ['Residential Rooftop', 'Commercial Rooftop', 'Utility Scale']],
            ['category' => 'Mounting / Installation', 'options' => ['Rooftop', 'Ground Mount', 'Tracker']],
            ['category' => 'Technology', 'options' => ['TOPCon', 'Bifacial', 'HJT']],
            ['category' => 'Bankability', 'options' => ['Bloomberg Tier 1', 'Approved Vendor']],
            ['category' => 'Protection / Features', 'options' => ['AFCI', 'RCMU', 'DC SPD']],
        ];
    }

    /**
     * @param  Collection<int,CompiledDeviceRecord>  $records
     * @return array<int,array<string,string>>
     */
    private function historyRows(DeviceDatasheet $datasheet, Collection $records): array
    {
        return collect([
            ['Event' => 'Uploaded', 'When' => $datasheet->created_at?->toDateString() ?? 'Pending', 'Details' => $datasheet->datasheet_original_filename ?: 'Datasheet uploaded'],
            ['Event' => 'Compiled', 'When' => $records->max('updated_at')?->toDateString() ?? 'Pending', 'Details' => $records->count().' structured engineering data set(s) created'],
            ['Event' => 'Metadata Ready', 'When' => now()->toDateString(), 'Details' => 'Metadata, tags, manufacturing links and supporting document workflows prepared'],
        ])->all();
    }

    private function powerLabel(CompiledDeviceRecord $record): string
    {
        if ($record->power_class_w) {
            return "{$record->power_class_w} W";
        }

        if ($record->power_class_kw) {
            return "{$record->power_class_kw} kW";
        }

        return 'Pending';
    }

    private function factoryLocationCount(?ManufacturerCompany $company): int
    {
        if (! $company || ! Schema::hasTable('manufacturer_factory_locations')) {
            return 0;
        }

        return $company->factoryLocations()->count();
    }

    private function distributionCountryCount(?ManufacturerCompany $company): int
    {
        if (! $company || ! Schema::hasTable('manufacturer_distribution_countries')) {
            return 0;
        }

        return $company->distributionCountries()->count();
    }

    /**
     * @return list<string>
     */
    private function productCategories(Request $request): array
    {
        return $this->visibleDatasheetQuery($request)
            ->select('device_type')
            ->distinct()
            ->pluck('device_type')
            ->filter()
            ->map(fn (string $deviceType): string => str_replace('_', ' ', ucfirst($deviceType)))
            ->values()
            ->all() ?: ['Modules', 'Inverters'];
    }

    /**
     * @return array<int,array<string,string|null>>
     */
    private function latestDatasheetRows(Request $request): array
    {
        return $this->visibleDatasheetQuery($request)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (DeviceDatasheet $datasheet): array => [
                'title' => $datasheet->product_name ?: $datasheet->datasheet_original_filename ?: 'Datasheet',
                'series' => $datasheet->series ?: 'Pending',
                'status' => $datasheet->status,
                'updated' => $datasheet->updated_at?->toDateString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string,mixed>  $metadata
     * @return array<int,array<string,string>>
     */
    private function previewContactRows(Request $request, array $metadata): array
    {
        $company = $request->user()?->manufacturerCompany;

        if ($company && Schema::hasTable('manufacturer_country_contacts')) {
            $rows = $company->countryContacts()
                ->latest()
                ->limit(4)
                ->get()
                ->map(fn ($contact): array => [
                    'label' => ucfirst((string) $contact->contact_type).' · '.$contact->country,
                    'value' => $contact->email ?: $contact->phone ?: 'Pending',
                ])
                ->values()
                ->all();

            if ($rows !== []) {
                return $rows;
            }
        }

        return [
            ['label' => 'Sales', 'value' => $metadata['sales_contact_email'] ?? 'Pending'],
            ['label' => 'Technical', 'value' => $metadata['technical_contact_email'] ?? 'Pending'],
            ['label' => 'Warranty / Service', 'value' => $metadata['service_contact_email'] ?? 'Pending'],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function companyPayload(Request $request): array
    {
        $user = $request->user();
        $company = $user?->manufacturerCompany;
        $plan = $company?->plan_code ?? 'pro';
        $isPlatformOperator = $user && in_array($user->role, [LineWattRole::SUPER_ADMIN, LineWattRole::ADMIN], true);

        return [
            'name' => $company?->name ?? 'All Manufacturers',
            'plan_code' => $plan,
            'plan_label' => match ($plan) {
                'enterprise' => 'Enterprise',
                default => 'Pro',
            },
            'subscription_status' => $company?->subscription_status ?? 'platform_access',
            'manufacturer_role_label' => match ($user?->manufacturer_role) {
                'manufacturer_admin' => 'Manufacturer Admin',
                'manufacturer_user' => 'Manufacturer User',
                default => $isPlatformOperator ? 'Platform Admin' : 'Manufacturer User',
            },
            'is_admin' => $isPlatformOperator || $user?->manufacturer_role === 'manufacturer_admin',
            'can_upgrade' => $user?->manufacturer_role === 'manufacturer_admin' && $plan === 'pro',
            'upgrade_message' => $user?->manufacturer_role === 'manufacturer_user'
                ? 'Please contact your Manufacturer Administrator to upgrade your subscription.'
                : null,
            'limits' => $this->planLimits($plan),
        ];
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

    /**
     * @return array<string,mixed>
     */
    private function planLimits(string $plan): array
    {
        return match ($plan) {
            'enterprise' => ['promotions' => true, 'insights' => true, 'website_integration' => true, 'datasheet_designer' => true],
            'pro' => ['promotions' => true, 'insights' => true, 'website_integration' => false, 'datasheet_designer' => false],
            default => ['promotions' => true, 'insights' => true, 'website_integration' => false, 'datasheet_designer' => false],
        };
    }

    private function safeFilename(string $filename): string
    {
        return str_replace(['"', "\r", "\n"], '', $filename);
    }
}
