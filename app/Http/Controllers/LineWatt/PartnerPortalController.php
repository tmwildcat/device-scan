<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\LineWattRole;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\ManufacturerCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class PartnerPortalController extends Controller
{
    public function __invoke(): Response
    {
        $user = auth()->user();
        $company = $user?->manufacturerCompany;
        $isPlatformOperator = $user && in_array($user->role, [LineWattRole::SUPER_ADMIN, LineWattRole::ADMIN], true);
        $manufacturerNames = $this->manufacturerNames($company);
        $summary = [
            'datasheets' => 0,
            'models' => 0,
            'structured_engineering_data' => 0,
            'supporting_documents' => 0,
            'submissions' => 0,
            'pending_reviews' => 0,
            'recent_updates' => 0,
            'promotion_campaigns' => 0,
            'central_records_available' => 0,
            'downloads_last_30_days' => 0,
            'engineering_views' => 0,
            'comparisons' => 0,
            'product_enquiries' => 0,
            'promotion_performance' => 0,
            'draft_saved_review' => 0,
            'submitted_for_approval' => 0,
            'changes_requested' => 0,
            'published' => 0,
        ];
        $recentDatasheets = [];
        $pendingReviews = [];
        $recentStructuredData = [];
        $companyUserCount = $company ? $company->users()->count() : 0;

        if (Schema::hasTable('device_datasheets')) {
            $datasheetQuery = DeviceDatasheet::query()
                ->whereIn('source_type', ['central_curated', 'partner_submitted'])
                ->when(! $isPlatformOperator && $company, fn (Builder $query) => $query->whereIn('manufacturer', $manufacturerNames));

            $summary['submissions'] = (clone $datasheetQuery)->count();
            $summary['datasheets'] = (clone $datasheetQuery)->count();
            $summary['supporting_documents'] = (clone $datasheetQuery)->count();
            $recentDatasheets = (clone $datasheetQuery)
                ->withCount('compiledRecords')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (DeviceDatasheet $datasheet): array => $this->datasheetRow($datasheet))
                ->all();
        }

        if (Schema::hasTable('compiled_device_records')) {
            $partnerRecords = CompiledDeviceRecord::query()
                ->where('source_type', 'partner_submitted')
                ->when(! $isPlatformOperator && $company, fn (Builder $query) => $query->whereIn('manufacturer', $manufacturerNames));
            $centralRecords = CompiledDeviceRecord::query()
                ->where('source_type', 'central_curated')
                ->when(! $isPlatformOperator && $company, fn (Builder $query) => $query->whereIn('manufacturer', $manufacturerNames));
            $allManufacturerRecords = CompiledDeviceRecord::query()
                ->whereIn('source_type', ['central_curated', 'partner_submitted'])
                ->when(! $isPlatformOperator && $company, fn (Builder $query) => $query->whereIn('manufacturer', $manufacturerNames));

            $summary['models'] = (clone $allManufacturerRecords)->count();
            $summary['structured_engineering_data'] = (clone $allManufacturerRecords)->count();
            $summary['pending_reviews'] = (clone $allManufacturerRecords)->whereIn('status', ['compiled', 'review_required', 'publisher_review', 'changes_requested'])->count();
            $summary['draft_saved_review'] = (clone $allManufacturerRecords)->whereIn('status', ['compiled', 'review_required', 'publisher_review'])->count();
            $summary['submitted_for_approval'] = (clone $allManufacturerRecords)
                ->where(fn (Builder $query) => $query->where('status', 'submitted_for_approval')->orWhere('review_status', 'submitted'))
                ->count();
            $summary['changes_requested'] = (clone $allManufacturerRecords)->where('status', 'changes_requested')->count();
            $summary['published'] = (clone $allManufacturerRecords)->where('status', 'published')->count();
            $summary['central_records_available'] = (clone $centralRecords)->count();
            $summary['recent_updates'] = (clone $centralRecords)->where('updated_at', '>=', now()->subDays(30))->count();

            $pendingReviews = (clone $allManufacturerRecords)
                ->with('datasheet')
                ->whereIn('status', ['compiled', 'review_required', 'publisher_review', 'changes_requested'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (CompiledDeviceRecord $record): array => $this->structuredDataRow($record))
                ->all();

            $recentStructuredData = (clone $centralRecords)
                ->with('datasheet')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (CompiledDeviceRecord $record): array => $this->structuredDataRow($record))
                ->all();
        }

        return Inertia::render('LineWatt/PartnerPortal', [
            'workspace' => [
                'name' => 'Manufacturer Admin',
                'role' => $user?->role,
                'role_label' => LineWattRole::label($user?->role),
            ],
            'company' => $this->companyPayload($company, $user?->manufacturer_role, $companyUserCount, $isPlatformOperator),
            'summary' => $summary,
            'recentDatasheets' => $recentDatasheets,
            'pendingReviews' => $pendingReviews,
            'recentStructuredData' => $recentStructuredData,
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function datasheetRow(DeviceDatasheet $datasheet): array
    {
        return [
            'id' => $datasheet->id,
            'title' => $datasheet->product_name ?: $datasheet->datasheet_original_filename ?: 'Datasheet',
            'filename' => $datasheet->datasheet_original_filename ?: 'datasheet.pdf',
            'family_series' => $datasheet->series ?: $datasheet->product_name ?: 'Pending',
            'models_count' => $datasheet->compiled_records_count ?? 0,
            'revision' => $datasheet->metadata['revision'] ?? 'v1',
            'language' => $datasheet->metadata['language'] ?? 'English',
            'status' => $datasheet->status,
            'uploaded' => $datasheet->created_at?->toDateString(),
            'preview_href' => route('admin.manufacturer.datasheets.pdf', ['datasheet' => $datasheet->id]),
            'show_href' => route('admin.manufacturer.datasheets.show', ['datasheet' => $datasheet->id]),
            'replace_href' => route('partner.submissions.new'),
            'history_href' => route('admin.manufacturer.datasheets.show', ['datasheet' => $datasheet->id, 'tab' => 'History']),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function structuredDataRow(CompiledDeviceRecord $record): array
    {
        return [
                    'id' => $record->id,
                    'uuid' => $record->uuid,
            'model' => $record->display_name ?: $record->model_name ?: $record->model_series ?: 'Model pending',
            'datasheet' => $record->datasheet?->datasheet_original_filename ?: $record->datasheet?->product_name ?: 'Datasheet pending',
                    'status' => $record->status,
            'compiler_version' => $record->compiler_version,
            'validation_grade' => $record->validation_grade,
                    'updated_at' => $record->updated_at?->toDateString(),
            'open_href' => route('records.show', ['record' => $record->uuid ?: $record->id]),
            'review_href' => route('admin.manufacturer.engineering-data.review', ['record' => $record->uuid ?: $record->id]),
            'datasheet_href' => $record->datasheet
                ? route('admin.manufacturer.datasheets.show', ['datasheet' => $record->datasheet->id])
                : null,
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

        $metadata = $company->metadata ?? [];

        return collect($metadata['manufacturer_aliases'] ?? [])
            ->push($company->name)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string,mixed>
     */
    private function companyPayload(?ManufacturerCompany $company, ?string $manufacturerRole, int $userCount, bool $isPlatformOperator): array
    {
        $plan = $company?->plan_code ?? 'pro';
        $role = $manufacturerRole ?: ($isPlatformOperator ? 'platform_admin' : 'manufacturer_user');

        return [
            'name' => $company?->name ?? 'All Manufacturers',
            'logo_placeholder' => true,
            'plan_code' => $plan,
            'plan_label' => match ($plan) {
                'enterprise' => 'Enterprise',
                default => 'Pro',
            },
            'subscription_status' => $company?->subscription_status ?? 'platform_access',
            'max_users' => $company?->max_users ?? null,
            'user_count' => $userCount,
            'manufacturer_role' => $role,
            'manufacturer_role_label' => match ($role) {
                'manufacturer_admin' => 'Manufacturer Admin',
                'platform_admin' => 'Platform Admin',
                default => 'Manufacturer User',
            },
            'is_admin' => in_array($role, ['manufacturer_admin', 'platform_admin'], true),
            'is_platform_operator' => $isPlatformOperator,
            'can_upgrade' => $role === 'manufacturer_admin' && $plan === 'pro',
            'upgrade_message' => $role === 'manufacturer_user' ? 'Please contact your Manufacturer Administrator to upgrade your subscription.' : null,
            'limits' => $this->planLimits($plan),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function planLimits(string $plan): array
    {
        return match ($plan) {
            'enterprise' => [
                'max_users' => 10,
                'promotions' => true,
                'insights' => true,
                'website_integration' => true,
                'datasheet_designer' => true,
                'advanced_content' => true,
            ],
            'pro' => [
                'max_users' => 3,
                'promotions' => true,
                'insights' => true,
                'website_integration' => false,
                'datasheet_designer' => false,
                'advanced_content' => false,
            ],
            default => [
                'max_users' => 3,
                'promotions' => true,
                'insights' => true,
                'website_integration' => false,
                'datasheet_designer' => false,
                'advanced_content' => false,
            ],
        };
    }
}
