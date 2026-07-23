<?php

use App\Http\Controllers\DeviceScan\Debug\InverterCompilerDebugController;
use App\Http\Controllers\DeviceScan\Debug\ModuleCompilerDebugController;
use App\Http\Controllers\LegalAcceptanceController;
use App\Http\Controllers\LegalGovernance\LegalCounselController;
use App\Http\Controllers\LegalGovernance\LegalOperationsController;
use App\Http\Controllers\LegalGovernance\PublicLegalPortalController;
use App\Http\Controllers\LineWatt\BusinessAdminController;
use App\Http\Controllers\LineWatt\CentralLibraryController;
use App\Http\Controllers\LineWatt\CentralReviewController;
use App\Http\Controllers\LineWatt\ChampionDashboardController;
use App\Http\Controllers\LineWatt\CompareController;
use App\Http\Controllers\LineWatt\CompareSelectController;
use App\Http\Controllers\LineWatt\ComparisonExportController;
use App\Http\Controllers\LineWatt\DashboardRedirectController;
use App\Http\Controllers\LineWatt\DatasheetReviewController;
use App\Http\Controllers\LineWatt\EngineeringRecordController;
use App\Http\Controllers\LineWatt\EngineeringRecordExportController;
use App\Http\Controllers\LineWatt\EngineeringSearchController;
use App\Http\Controllers\LineWatt\HomeController;
use App\Http\Controllers\LineWatt\InternalAppAccessController;
use App\Http\Controllers\LineWatt\LibraryChampionAdminController;
use App\Http\Controllers\LineWatt\LibraryMemberController;
use App\Http\Controllers\LineWatt\LibraryPublisherAdminController;
use App\Http\Controllers\LineWatt\ManufacturerCompanyDataController;
use App\Http\Controllers\LineWatt\ManufacturerProductController;
use App\Http\Controllers\LineWatt\ManufacturerUpgradeController;
use App\Http\Controllers\LineWatt\ManufacturerUserController;
use App\Http\Controllers\LineWatt\MyLibraryController;
use App\Http\Controllers\LineWatt\MyLibraryReviewQueueController;
use App\Http\Controllers\LineWatt\MyLibraryStorageController;
use App\Http\Controllers\LineWatt\NotificationController;
use App\Http\Controllers\LineWatt\OemSubscriberController;
use App\Http\Controllers\LineWatt\PartnerPortalController;
use App\Http\Controllers\LineWatt\PartnerRequestController;
use App\Http\Controllers\LineWatt\PlatformAdminController;
use App\Http\Controllers\LineWatt\PlatformDiscoveryController;
use App\Http\Controllers\LineWatt\PlatformServiceController;
use App\Http\Controllers\LineWatt\PowerSearchAdminController;
use App\Http\Controllers\LineWatt\PromotionController;
use App\Http\Controllers\LineWatt\PublisherWorkspaceController;
use App\Http\Controllers\LineWatt\PvsystImportController;
use App\Http\Controllers\LineWatt\SeoDiscoveryAdminController;
use App\Http\Controllers\LineWatt\SeoPublicController;
use App\Http\Controllers\LineWatt\SeoSitemapController;
use App\Http\Controllers\LineWatt\UploadCompiledRecordsController;
use App\Http\Controllers\LineWatt\UploadController;
use App\Models\ManufacturerCompany;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', HomeController::class)->name('home');
Route::get('/legal', [PublicLegalPortalController::class, 'index'])->name('legal.index');
Route::get('/legal/{slug}/artifacts/{version}/{type}', [PublicLegalPortalController::class, 'artifact'])->middleware('throttle:30,1')->name('legal.artifact');
Route::get('/legal/{slug}/{version?}', [PublicLegalPortalController::class, 'show'])->name('legal.show');
Route::get('/sitemap.xml', [SeoSitemapController::class, 'index'])->name('seo.sitemap');
Route::get('/manufacturers.xml', [SeoSitemapController::class, 'manufacturers'])->name('seo.sitemap.manufacturers');
Route::get('/datasheets.xml', [SeoSitemapController::class, 'datasheets'])->name('seo.sitemap.datasheets');
Route::get('/models.xml', [SeoSitemapController::class, 'models'])->name('seo.sitemap.models');
Route::get('/technology.xml', [SeoSitemapController::class, 'technology'])->name('seo.sitemap.technology');
Route::get('/applications.xml', [SeoSitemapController::class, 'applications'])->name('seo.sitemap.applications');
Route::get('/search/manufacturers', [EngineeringSearchController::class, 'manufacturers'])
    ->middleware('entitlement:library.search')
    ->name('engineering-search.manufacturers');
Route::get('/search/results', [EngineeringSearchController::class, 'results'])
    ->middleware('entitlement:library.search')
    ->name('engineering-search.results');
Route::get('/search/modules', [EngineeringSearchController::class, 'modules'])
    ->middleware('entitlement:library.search')
    ->name('engineering-search.modules');
Route::get('/search/inverters', [EngineeringSearchController::class, 'inverters'])
    ->middleware('entitlement:library.search')
    ->name('engineering-search.inverters');
Route::get('/search', EngineeringSearchController::class)
    ->middleware('entitlement:library.search')
    ->name('engineering-search');
Route::get('/manufacturers', [EngineeringSearchController::class, 'manufacturersIndex'])
    ->name('manufacturers.index');
Route::get('/manufacturers/{manufacturer}', [EngineeringSearchController::class, 'manufacturer'])
    ->name('manufacturers.show');
Route::get('/datasheets/{slug}', [SeoPublicController::class, 'datasheet'])->name('seo.datasheets.show');
Route::get('/models/{slug}', [SeoPublicController::class, 'model'])->name('seo.models.show');
Route::get('/technology/{slug}', [SeoPublicController::class, 'landing'])->defaults('kind', 'technology')->name('seo.technology.show');
Route::get('/applications/{slug}', [SeoPublicController::class, 'landing'])->defaults('kind', 'application')->name('seo.applications.show');
Route::get('/search/{slug}', [SeoPublicController::class, 'landing'])->defaults('kind', 'power_search')->name('seo.power-search.show');
Route::get('/compare/{slug}', [SeoPublicController::class, 'landing'])->defaults('kind', 'comparison')->name('seo.compare.show');
Route::get('/learn/{slug}', [SeoPublicController::class, 'landing'])->defaults('kind', 'knowledge')->name('seo.knowledge.show');
Route::get('/records/{record}', [EngineeringRecordController::class, 'show'])
    ->middleware('entitlement:library.view_record')
    ->name('records.show');
Route::get('/library/records/{record}', [EngineeringRecordController::class, 'show'])
    ->middleware('entitlement:library.view_record')
    ->name('library.records.show');

Route::get('/partner/apply', [PartnerRequestController::class, 'create'])
    ->name('partner.apply');

Route::post('/partner/apply', [PartnerRequestController::class, 'store'])
    ->name('partner.apply.store');

Route::get('/manufacturer/register/{token}', [OemSubscriberController::class, 'register'])
    ->name('manufacturer.register');

Route::post('/manufacturer/register/{token}', [OemSubscriberController::class, 'accept'])
    ->name('manufacturer.register.accept');

Route::get('/device-scan/debug/module-compiler', ModuleCompilerDebugController::class)
    ->withoutMiddleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        PreventRequestForgery::class,
        StartSession::class,
        ShareErrorsFromSession::class,
    ])
    ->name('device-scan.debug.module-compiler');

Route::get('/device-scan/debug/inverter-compiler', InverterCompilerDebugController::class)
    ->withoutMiddleware([
        EncryptCookies::class,
        AddQueuedCookiesToResponse::class,
        PreventRequestForgery::class,
        StartSession::class,
        ShareErrorsFromSession::class,
    ])
    ->name('device-scan.debug.inverter-compiler');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/legal-status', [LegalAcceptanceController::class, 'status'])->name('legal.status');
    Route::get('/legal/acceptance', [LegalAcceptanceController::class, 'index'])->name('legal.acceptance.index');
    Route::post('/legal/acceptance/{obligation}', [LegalAcceptanceController::class, 'accept'])->name('legal.acceptance.store');

    Route::prefix('admin/legal-governance')->name('legal-governance.')->group(function (): void {
        Route::get('/', [LegalCounselController::class, 'dashboard'])->middleware('legal.permission:legal.dashboard.view')->name('dashboard');
        Route::get('/documents', [LegalCounselController::class, 'documents'])->middleware('legal.permission:legal.documents.view')->name('documents');
        Route::get('/versions/{version}/edit', [LegalCounselController::class, 'edit'])->middleware('legal.permission:legal.documents.edit')->name('versions.edit');
        Route::put('/versions/{version}', [LegalCounselController::class, 'update'])->middleware('legal.permission:legal.documents.edit')->name('versions.update');
        Route::post('/versions/{version}/submit-review', [LegalOperationsController::class, 'submitReview'])->middleware('legal.permission:legal.versions.submit_review')->name('versions.submit-review');
        Route::post('/versions/{version}/reviews', [LegalOperationsController::class, 'decision'])->middleware('legal.permission:legal.versions.review')->name('reviews.store');
        Route::post('/versions/{version}/approve', [LegalOperationsController::class, 'approve'])->middleware('legal.permission:legal.versions.approve')->name('versions.approve');
        Route::get('/reviews', [LegalOperationsController::class, 'reviews'])->middleware('legal.permission:legal.reviews.view')->name('reviews.index');
        Route::get('/reviews/{version}', [LegalOperationsController::class, 'review'])->middleware('legal.permission:legal.reviews.view')->name('reviews.show');
        Route::post('/versions/{version}/return-to-draft', [LegalOperationsController::class, 'returnToDraft'])->middleware('legal.permission:legal.versions.return_to_draft')->name('versions.return-to-draft');
        Route::get('/publications', [LegalOperationsController::class, 'publications'])->middleware('legal.permission:legal.publications.view')->name('publications.index');
        Route::post('/versions/{version}/schedule', [LegalOperationsController::class, 'schedule'])->middleware('legal.permission:legal.versions.schedule')->name('versions.schedule');
        Route::delete('/versions/{version}/schedule', [LegalOperationsController::class, 'cancelSchedule'])->middleware('legal.permission:legal.versions.cancel_schedule')->name('versions.schedule.cancel');
        Route::post('/versions/{version}/publish', [LegalOperationsController::class, 'publish'])->middleware('legal.permission:legal.versions.publish')->name('versions.publish');
        Route::post('/versions/{version}/withdraw', [LegalOperationsController::class, 'withdraw'])->middleware('legal.permission:legal.versions.withdraw')->name('versions.withdraw');
        Route::post('/versions/{version}/archive', [LegalOperationsController::class, 'archive'])->middleware('legal.permission:legal.versions.archive')->name('versions.archive');
        Route::get('/workflows', [LegalOperationsController::class, 'workflows'])->middleware('legal.permission:legal.workflows.view')->name('workflows.index');
        Route::get('/workflows/{workflow}', [LegalOperationsController::class, 'workflow'])->middleware('legal.permission:legal.workflows.view')->name('workflows.show');
        Route::put('/workflows/{workflow}', [LegalOperationsController::class, 'updateWorkflow'])->middleware('legal.permission:legal.workflows.edit')->name('workflows.update');
        Route::post('/workflows/{workflow}/requirements', [LegalOperationsController::class, 'storeWorkflowRequirement'])->middleware('legal.permission:legal.workflows.edit')->name('workflows.requirements.store');
        Route::delete('/workflows/{workflow}/requirements/{requirement}', [LegalOperationsController::class, 'destroyWorkflowRequirement'])->middleware('legal.permission:legal.workflows.edit')->name('workflows.requirements.destroy');
        Route::post('/workflows/{workflow}/activate', [LegalOperationsController::class, 'activateWorkflow'])->middleware('legal.permission:legal.workflows.activate')->name('workflows.activate');
        Route::get('/evidence-exports', [LegalOperationsController::class, 'evidence'])->middleware('legal.permission:legal.acceptances.export')->name('evidence-exports.index');
        Route::post('/evidence-exports', [LegalOperationsController::class, 'exportEvidence'])->middleware('legal.permission:legal.acceptances.export')->name('evidence-exports.store');
        Route::get('/placeholders', [LegalOperationsController::class, 'placeholders'])->middleware('legal.permission:legal.placeholders.view')->name('placeholders.index');
        Route::patch('/placeholders/{placeholder}', [LegalOperationsController::class, 'updatePlaceholder'])->middleware('legal.permission:legal.placeholders.manage')->name('placeholders.update');
        Route::get('/settings', [LegalOperationsController::class, 'settings'])->middleware('legal.permission:legal.settings.manage')->name('settings');
        Route::get('/{section}', [LegalCounselController::class, 'section'])->middleware('legal.permission:legal.dashboard.view')->name('section');
    });

    Route::get('/dashboard', DashboardRedirectController::class)
        ->middleware('legal.acceptance:platform.registered.access')
        ->name('dashboard');

    Route::get('/champion', [ChampionDashboardController::class, 'index'])
        ->name('champion');

    Route::get('/champion/manufacturers/{manufacturer}', [ChampionDashboardController::class, 'manufacturer'])
        ->name('champion.manufacturers.show');

    Route::get('/my-library', MyLibraryController::class)
        ->middleware(['workspace:my-library', 'legal.acceptance:library.private_workspace.access'])
        ->name('my-library');

    Route::get('/my-library/review-queue', MyLibraryReviewQueueController::class)
        ->middleware('workspace:my-library')
        ->name('my-library.review-queue');

    Route::get('/my-library/storage', MyLibraryStorageController::class)
        ->middleware(['workspace:my-library', 'legal.acceptance:library.private_workspace.access'])
        ->name('my-library.storage');

    Route::delete('/my-library/storage/items/{item}', [MyLibraryStorageController::class, 'destroy'])
        ->where('item', '.*')
        ->middleware(['workspace:my-library', 'legal.acceptance:library.private_workspace.access'])
        ->name('my-library.storage.items.destroy');

    Route::delete('/my-library/storage/items', [MyLibraryStorageController::class, 'destroySelected'])
        ->middleware(['workspace:my-library', 'legal.acceptance:library.private_workspace.access'])
        ->name('my-library.storage.items.destroy-selected');

    Route::get('/upload', UploadController::class)
        ->middleware('entitlement:library.private_upload')
        ->name('upload');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');

    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');

    Route::get('/uploads/manufacturers', [UploadController::class, 'manufacturers'])
        ->middleware('auth')
        ->name('uploads.manufacturers');

    Route::get('/my-library/uploads/new', [UploadController::class, 'createSubscriber'])
        ->middleware(['entitlement:library.private_upload', 'legal.acceptance:library.private_workspace.access'])
        ->name('my-library.uploads.new');

    Route::post('/my-library/uploads', [UploadController::class, 'storeSubscriber'])
        ->middleware(['entitlement:library.private_upload', 'legal.acceptance:library.private_workspace.access'])
        ->name('my-library.uploads.store');

    Route::post('/my-library/pvsyst-import', [PvsystImportController::class, 'store'])
        ->middleware(['entitlement:library.private_upload', 'legal.acceptance:library.private_workspace.access'])
        ->name('my-library.pvsyst-import.store');

    Route::get('/admin/library', CentralLibraryController::class)
        ->middleware('workspace:central')
        ->name('admin.library');

    Route::get('/admin/business', BusinessAdminController::class)
        ->middleware('workspace:business')
        ->name('admin.business');

    Route::get('/admin/business/{section}', [BusinessAdminController::class, 'placeholder'])
        ->whereIn('section', ['compiler'])
        ->middleware('workspace:business')
        ->name('admin.business.placeholder');

    Route::get('/admin/business/discovery', [SeoDiscoveryAdminController::class, 'dashboard'])
        ->middleware('workspace:business')
        ->name('admin.business.discovery');

    Route::get('/admin/business/discovery/{page}', [SeoDiscoveryAdminController::class, 'page'])
        ->whereIn('page', ['canonical-urls', 'landing-pages', 'redirects', 'sitemaps', 'structured-data', 'ai-discoverability'])
        ->middleware('workspace:business')
        ->name('admin.business.discovery.page');

    Route::post('/admin/business/discovery/redirects', [SeoDiscoveryAdminController::class, 'storeRedirect'])
        ->middleware('workspace:business')
        ->name('admin.business.discovery.redirects.store');

    Route::get('/central-library', fn () => redirect()->route('admin.library'))
        ->middleware('workspace:central')
        ->name('central-library');

    Route::get('/admin/library/uploads/new', [UploadController::class, 'createCentral'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.uploads.new');

    Route::post('/admin/library/uploads', [UploadController::class, 'storeCentral'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.uploads.store');

    Route::get('/central-library/uploads/new', fn () => redirect()->route('admin.library.uploads.new'))
        ->middleware('entitlement:central.manage')
        ->name('central-library.uploads.new');

    Route::post('/central-library/uploads', [UploadController::class, 'storeCentral'])
        ->middleware('entitlement:central.manage')
        ->name('central-library.uploads.store');

    Route::get('/admin/library/power-search', [PowerSearchAdminController::class, 'index'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.power-search');

    Route::get('/admin/library/promotions', [PromotionController::class, 'index'])
        ->middleware('workspace:business')
        ->name('admin.library.promotions');

    Route::post('/admin/library/promotions', [PromotionController::class, 'store'])
        ->middleware('workspace:business')
        ->name('admin.library.promotions.store');

    Route::patch('/admin/library/promotions/{promotion}', [PromotionController::class, 'update'])
        ->middleware('workspace:business')
        ->name('admin.library.promotions.update');

    Route::post('/admin/library/promotions/{promotion}/pause', [PromotionController::class, 'pause'])
        ->middleware('workspace:business')
        ->name('admin.library.promotions.pause');

    Route::post('/admin/library/promotions/{promotion}/archive', [PromotionController::class, 'archive'])
        ->middleware('workspace:business')
        ->name('admin.library.promotions.archive');

    Route::get('/admin/library/champions', [LibraryChampionAdminController::class, 'index'])
        ->middleware('workspace:business')
        ->name('admin.library.champions');

    Route::post('/admin/library/champions', [LibraryChampionAdminController::class, 'store'])
        ->middleware('workspace:business')
        ->name('admin.library.champions.store');

    Route::get('/admin/library/champions/search', [LibraryChampionAdminController::class, 'search'])
        ->middleware('workspace:business')
        ->name('admin.library.champions.search');

    Route::get('/admin/library/champions/{champion}', [LibraryChampionAdminController::class, 'show'])
        ->middleware('workspace:business')
        ->name('admin.library.champions.show');

    Route::get('/admin/library/champions/{champion}/manufacturers/search', [LibraryChampionAdminController::class, 'manufacturerSearch'])
        ->middleware('workspace:business')
        ->name('admin.library.champions.manufacturers.search');

    Route::post('/admin/library/champions/{champion}/manufacturers', [LibraryChampionAdminController::class, 'assignManufacturer'])
        ->middleware('workspace:business')
        ->name('admin.library.champions.manufacturers.assign');

    Route::delete('/admin/library/champions/{champion}/manufacturers/{manufacturer}', [LibraryChampionAdminController::class, 'removeManufacturer'])
        ->middleware('workspace:business')
        ->name('admin.library.champions.manufacturers.remove');

    Route::patch('/admin/library/champions/{champion}', [LibraryChampionAdminController::class, 'update'])
        ->middleware('workspace:business')
        ->name('admin.library.champions.update');

    Route::post('/admin/library/champions/{champion}/pause', [LibraryChampionAdminController::class, 'pause'])
        ->middleware('workspace:business')
        ->name('admin.library.champions.pause');

    Route::post('/admin/library/champions/{champion}/suspend', [LibraryChampionAdminController::class, 'suspend'])
        ->middleware('workspace:business')
        ->name('admin.library.champions.suspend');

    Route::get('/admin/library/datasheets/{datasheet}/review', [DatasheetReviewController::class, 'central'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.datasheets.review');

    Route::get('/admin/library/datasheets/{datasheet}/review/source-pdf', [DatasheetReviewController::class, 'sourcePdfCentral'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.datasheets.review.source-pdf');

    Route::patch('/admin/library/datasheets/{datasheet}/review', [DatasheetReviewController::class, 'saveCentral'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.datasheets.review.save');

    Route::post('/admin/library/datasheets/{datasheet}/review/approve', [DatasheetReviewController::class, 'approveCentral'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.datasheets.review.approve');

    Route::post('/admin/library/datasheets/{datasheet}/review/reject', [DatasheetReviewController::class, 'rejectCentral'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.datasheets.review.reject');

    Route::post('/admin/library/datasheets/{datasheet}/review/changes-requested', [DatasheetReviewController::class, 'requestChangesCentral'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.datasheets.review.changes-requested');

    Route::get('/admin/library/approval-queue', [CentralLibraryController::class, 'approvalQueue'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.approval-queue');

    Route::get('/admin/library/engineering-data', [CentralLibraryController::class, 'engineeringData'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.engineering-data');

    Route::get('/admin/library/review', [CentralLibraryController::class, 'review'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.review.index');

    Route::get('/admin/library/pending-approval', [CentralLibraryController::class, 'pendingApproval'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.pending-approval');

    Route::get('/admin/library/changes-requested', [CentralLibraryController::class, 'changesRequested'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.changes-requested');

    Route::get('/admin/library/published', [CentralLibraryController::class, 'published'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.published');

    Route::get('/admin/library/oem-subscribers', [OemSubscriberController::class, 'index'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.oem-subscribers');

    Route::get('/admin/library/oem-subscribers/new', [OemSubscriberController::class, 'create'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.oem-subscribers.new');

    Route::post('/admin/library/oem-subscribers', [OemSubscriberController::class, 'store'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.oem-subscribers.store');

    Route::get('/admin/library/oem-subscribers/manufacturers', [OemSubscriberController::class, 'manufacturers'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.oem-subscribers.manufacturers');

    Route::get('/admin/library/oem-subscribers/{subscriber}', [OemSubscriberController::class, 'show'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.oem-subscribers.show');

    Route::get('/admin/library/oems', fn () => redirect()->route('admin.library.oem-subscribers'))
        ->middleware('entitlement:central.manage')
        ->name('admin.library.oems');

    Route::get('/admin/library/oems/{oem}', fn (ManufacturerCompany $oem) => redirect()->route('admin.library.oem-subscribers.show', ['subscriber' => $oem]))
        ->middleware('entitlement:central.manage')
        ->name('admin.library.oems.show');

    Route::get('/admin/library/manufacturers', [CentralLibraryController::class, 'manufacturers'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.manufacturers');

    Route::get('/admin/library/manufacturers/search', [CentralLibraryController::class, 'manufacturerSearch'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.manufacturers.search');

    Route::get('/admin/library/manufacturers/all', fn () => redirect()->route('admin.library.manufacturers'))
        ->middleware('entitlement:central.manage');

    Route::get('/admin/library/manufacturers/{manufacturer}', [CentralLibraryController::class, 'manufacturerInventory'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.manufacturers.inventory');

    Route::get('/admin/library/members', [LibraryMemberController::class, 'index'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.members');

    Route::get('/admin/library/members/{member}', [LibraryMemberController::class, 'show'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.members.show');

    Route::post('/admin/library/members/{member}/suspend', [LibraryMemberController::class, 'suspend'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.members.suspend');

    Route::post('/admin/library/members/{member}/reactivate', [LibraryMemberController::class, 'reactivate'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.members.reactivate');

    Route::get('/admin/library/partner-requests', [PartnerRequestController::class, 'index'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.partner-requests');

    Route::get('/admin/library/partner-requests/{partnerRequest}', [PartnerRequestController::class, 'show'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.partner-requests.show');

    Route::post('/admin/library/partner-requests/{partnerRequest}/approve', [PartnerRequestController::class, 'approve'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.partner-requests.approve');

    Route::post('/admin/library/partner-requests/{partnerRequest}/reject', [PartnerRequestController::class, 'reject'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.partner-requests.reject');

    Route::post('/admin/library/partner-requests/{partnerRequest}/request-info', [PartnerRequestController::class, 'requestInfo'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.partner-requests.request-info');

    Route::get('/admin/library/datasheets', [CentralLibraryController::class, 'allDatasheets'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.datasheets.all');

    Route::get('/admin/library/datasheets/all', fn () => redirect()->route('admin.library.datasheets.all', ['view' => 'all']))
        ->middleware('entitlement:central.manage');

    Route::get('/admin/library/publishers', [LibraryPublisherAdminController::class, 'index'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.publishers');

    Route::get('/admin/library/publishers/new', [LibraryPublisherAdminController::class, 'create'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.publishers.create');

    Route::get('/admin/library/publishers/queue', fn () => redirect()->route('admin.library.publishers'))
        ->middleware('entitlement:central.manage');

    Route::get('/admin/library/publishers/{publisher}', [LibraryPublisherAdminController::class, 'show'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.publishers.show');

    Route::get('/admin/library/{section}/{page?}', [CentralLibraryController::class, 'placeholder'])
        ->whereIn('section', ['datasheets', 'engineering-data', 'manufacturers', 'governance', 'operations', 'settings'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.placeholder');

    Route::post('/admin/library/power-search/categories', [PowerSearchAdminController::class, 'storeCategory'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.power-search.categories.store');

    Route::post('/admin/library/power-search/options', [PowerSearchAdminController::class, 'storeOption'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.power-search.options.store');

    Route::post('/admin/library/power-search/assignments', [PowerSearchAdminController::class, 'assign'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.power-search.assignments.store');

    Route::get('/central-library/power-search', fn () => redirect()->route('admin.library.power-search'))
        ->middleware('entitlement:central.manage')
        ->name('central-library.power-search');

    Route::get('/admin/library/review/{record}', [CentralReviewController::class, 'central'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.review');

    Route::get('/admin/library/review/{record}/source-pdf', [CentralReviewController::class, 'sourcePdfCentral'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.review.source-pdf');

    Route::patch('/admin/library/review/{record}', [CentralReviewController::class, 'saveCentral'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.review.save');

    Route::post('/admin/library/review/{record}/approve', [CentralReviewController::class, 'approveCentral'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.review.approve');

    Route::post('/admin/library/review/{record}/reject', [CentralReviewController::class, 'rejectCentral'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.review.reject');

    Route::post('/admin/library/review/{record}/changes-requested', [CentralReviewController::class, 'requestChangesCentral'])
        ->middleware('entitlement:central.manage')
        ->name('admin.library.review.changes-requested');

    Route::get('/central-library/review/{record}', fn ($record) => redirect()->route('admin.library.review', ['record' => $record]))
        ->middleware('entitlement:central.manage')
        ->name('central-library.review');

    Route::get('/central-library/review/{record}/source-pdf', [CentralReviewController::class, 'sourcePdfCentral'])
        ->middleware('entitlement:central.manage')
        ->name('central-library.review.source-pdf');

    Route::patch('/central-library/review/{record}', [CentralReviewController::class, 'saveCentral'])
        ->middleware('entitlement:central.manage')
        ->name('central-library.review.save');

    Route::post('/central-library/review/{record}/approve', [CentralReviewController::class, 'approveCentral'])
        ->middleware('entitlement:central.manage')
        ->name('central-library.review.approve');

    Route::post('/central-library/review/{record}/reject', [CentralReviewController::class, 'rejectCentral'])
        ->middleware('entitlement:central.manage')
        ->name('central-library.review.reject');

    Route::post('/central-library/review/{record}/changes-requested', [CentralReviewController::class, 'requestChangesCentral'])
        ->middleware('entitlement:central.manage')
        ->name('central-library.review.changes-requested');

    Route::get('/publisher', [PublisherWorkspaceController::class, 'index'])
        ->middleware('workspace:publisher')
        ->name('publisher');

    Route::get('/publisher/uploads', [PublisherWorkspaceController::class, 'uploads'])
        ->middleware('workspace:publisher')
        ->name('publisher.uploads');

    Route::get('/publisher/uploads/new', [UploadController::class, 'createPublisher'])
        ->middleware('workspace:publisher')
        ->name('publisher.uploads.new');

    Route::post('/publisher/uploads', [UploadController::class, 'storePublisher'])
        ->middleware(['workspace:publisher', 'legal.acceptance:publisher.submission'])
        ->name('publisher.uploads.store');

    Route::get('/publisher/datasheets/{datasheet}/review', [DatasheetReviewController::class, 'publisher'])
        ->middleware('workspace:publisher')
        ->name('publisher.datasheets.review');

    Route::get('/publisher/datasheets/{datasheet}/review/source-pdf', [DatasheetReviewController::class, 'sourcePdfPublisher'])
        ->middleware('workspace:publisher')
        ->name('publisher.datasheets.review.source-pdf');

    Route::patch('/publisher/datasheets/{datasheet}/review', [DatasheetReviewController::class, 'savePublisher'])
        ->middleware(['workspace:publisher', 'legal.acceptance:publisher.submission'])
        ->name('publisher.datasheets.review.save');

    Route::post('/publisher/datasheets/{datasheet}/review/submit', [DatasheetReviewController::class, 'submitPublisher'])
        ->middleware(['workspace:publisher', 'legal.acceptance:publisher.submission'])
        ->name('publisher.datasheets.review.submit');

    Route::get('/publisher/review', [PublisherWorkspaceController::class, 'review'])
        ->middleware('workspace:publisher')
        ->name('publisher.review');

    Route::get('/publisher/submitted', [PublisherWorkspaceController::class, 'submitted'])
        ->middleware('workspace:publisher')
        ->name('publisher.submitted');

    Route::get('/publisher/changes-requested', [PublisherWorkspaceController::class, 'changesRequested'])
        ->middleware('workspace:publisher')
        ->name('publisher.changes-requested');

    Route::get('/publisher/search', [PublisherWorkspaceController::class, 'search'])
        ->middleware('workspace:publisher')
        ->name('publisher.search');

    Route::get('/publisher/oem-subscribers', [PublisherWorkspaceController::class, 'oemSubscribers'])
        ->middleware('workspace:publisher')
        ->name('publisher.oem-subscribers');

    Route::get('/publisher/review/{record}', [CentralReviewController::class, 'publisher'])
        ->middleware('workspace:publisher')
        ->name('publisher.review.show');

    Route::get('/publisher/review/{record}/source-pdf', [CentralReviewController::class, 'sourcePdfPublisher'])
        ->middleware('workspace:publisher')
        ->name('publisher.review.source-pdf');

    Route::patch('/publisher/review/{record}', [CentralReviewController::class, 'savePublisher'])
        ->middleware(['workspace:publisher', 'legal.acceptance:publisher.submission'])
        ->name('publisher.review.save');

    Route::post('/publisher/review/{record}/submit', [CentralReviewController::class, 'submitForApproval'])
        ->middleware(['workspace:publisher', 'legal.acceptance:publisher.submission'])
        ->name('publisher.review.submit');

    Route::get('/central-engineering', fn () => redirect()->route('admin.library'))
        ->middleware('workspace:central')
        ->name('central-engineering');

    Route::get('/admin/manufacturer', PartnerPortalController::class)
        ->middleware(['workspace:partner', 'legal.acceptance:manufacturer.portal.access'])
        ->name('admin.manufacturer');

    Route::get('/admin/manufacturer/company/profile', [ManufacturerProductController::class, 'companyProfile'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.profile');

    Route::post('/admin/manufacturer/company/profile/logo', [ManufacturerCompanyDataController::class, 'storeLogo'])
        ->middleware(['workspace:partner', 'legal.acceptance:manufacturer.portal.access'])
        ->name('admin.manufacturer.company.profile.logo.store');

    Route::delete('/admin/manufacturer/company/profile/logo', [ManufacturerCompanyDataController::class, 'destroyLogo'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.profile.logo.destroy');

    Route::get('/admin/manufacturer/company/profile/logo', [ManufacturerCompanyDataController::class, 'logo'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.profile.logo');

    Route::get('/admin/manufacturer/company/profile/qr.{format}', [ManufacturerCompanyDataController::class, 'qr'])
        ->whereIn('format', ['png', 'svg'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.profile.qr');

    Route::get('/admin/manufacturer/datasheets', [ManufacturerProductController::class, 'datasheets'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.datasheets');

    Route::get('/admin/manufacturer/datasheets/{datasheet}', [ManufacturerProductController::class, 'showDatasheet'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.datasheets.show');

    Route::get('/admin/manufacturer/models', [ManufacturerProductController::class, 'models'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.models');

    Route::get('/admin/manufacturer/structured-engineering-data', [ManufacturerProductController::class, 'structuredEngineeringData'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.structured-engineering-data');

    Route::get('/admin/manufacturer/supporting-documents', [ManufacturerProductController::class, 'supportingDocuments'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.supporting-documents');

    Route::get('/admin/manufacturer/factory-locations', [ManufacturerCompanyDataController::class, 'factories'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.factory-locations');

    Route::get('/admin/manufacturer/company/factories', [ManufacturerCompanyDataController::class, 'factories'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.factories');

    Route::get('/admin/manufacturer/company/factories/create', [ManufacturerCompanyDataController::class, 'createFactory'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.factories.create');

    Route::post('/admin/manufacturer/company/factories', [ManufacturerCompanyDataController::class, 'storeFactory'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.factories.store');

    Route::get('/admin/manufacturer/company/factories/{factory}/edit', [ManufacturerCompanyDataController::class, 'editFactory'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.factories.edit');

    Route::patch('/admin/manufacturer/company/factories/{factory}', [ManufacturerCompanyDataController::class, 'updateFactory'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.factories.update');

    Route::delete('/admin/manufacturer/company/factories/{factory}', [ManufacturerCompanyDataController::class, 'destroyFactory'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.factories.destroy');

    Route::get('/admin/manufacturer/distribution-countries', [ManufacturerCompanyDataController::class, 'distributionCountries'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.distribution-countries');

    Route::get('/admin/manufacturer/company/distribution-countries', [ManufacturerCompanyDataController::class, 'distributionCountries'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.distribution-countries');

    Route::get('/admin/manufacturer/company/distribution-countries/create', [ManufacturerCompanyDataController::class, 'createDistributionCountry'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.distribution-countries.create');

    Route::post('/admin/manufacturer/company/distribution-countries', [ManufacturerCompanyDataController::class, 'storeDistributionCountry'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.distribution-countries.store');

    Route::get('/admin/manufacturer/company/distribution-countries/{country}/edit', [ManufacturerCompanyDataController::class, 'editDistributionCountry'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.distribution-countries.edit');

    Route::patch('/admin/manufacturer/company/distribution-countries/{country}', [ManufacturerCompanyDataController::class, 'updateDistributionCountry'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.distribution-countries.update');

    Route::delete('/admin/manufacturer/company/distribution-countries/{country}', [ManufacturerCompanyDataController::class, 'destroyDistributionCountry'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.company.distribution-countries.destroy');

    Route::get('/admin/manufacturer/country-contacts', [ManufacturerCompanyDataController::class, 'countryContacts'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.country-contacts');

    Route::get('/admin/manufacturer/country-contacts/create', [ManufacturerCompanyDataController::class, 'createCountryContact'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.country-contacts.create');

    Route::post('/admin/manufacturer/country-contacts', [ManufacturerCompanyDataController::class, 'storeCountryContact'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.country-contacts.store');

    Route::get('/admin/manufacturer/country-contacts/{contact}/edit', [ManufacturerCompanyDataController::class, 'editCountryContact'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.country-contacts.edit');

    Route::patch('/admin/manufacturer/country-contacts/{contact}', [ManufacturerCompanyDataController::class, 'updateCountryContact'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.country-contacts.update');

    Route::delete('/admin/manufacturer/country-contacts/{contact}', [ManufacturerCompanyDataController::class, 'destroyCountryContact'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.country-contacts.destroy');

    Route::get('/admin/manufacturer/website-integration', [ManufacturerProductController::class, 'websiteIntegration'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.website-integration');

    Route::get('/admin/manufacturer/products', fn () => redirect()->route('admin.manufacturer.datasheets'))
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.products');

    Route::get('/admin/manufacturer/products/{record}', fn ($record) => redirect()->route('admin.manufacturer.datasheets'))
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.products.show');

    Route::get('/admin/manufacturer/datasheets/{datasheet}/pdf', [ManufacturerProductController::class, 'sourcePdf'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.datasheets.pdf');

    Route::get('/admin/manufacturer/datasheets/{datasheet}/review', [DatasheetReviewController::class, 'manufacturer'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.datasheets.review');

    Route::get('/admin/manufacturer/datasheets/{datasheet}/review/source-pdf', [DatasheetReviewController::class, 'sourcePdfManufacturer'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.datasheets.review.source-pdf');

    Route::patch('/admin/manufacturer/datasheets/{datasheet}/review', [DatasheetReviewController::class, 'saveManufacturer'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.datasheets.review.save');

    Route::post('/admin/manufacturer/datasheets/{datasheet}/review/submit', [DatasheetReviewController::class, 'submitManufacturer'])
        ->middleware(['workspace:partner', 'legal.acceptance:manufacturer.portal.access'])
        ->name('admin.manufacturer.datasheets.review.submit');

    Route::get('/admin/manufacturer/engineering-data/{record}/review', [CentralReviewController::class, 'manufacturer'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.engineering-data.review');

    Route::get('/admin/manufacturer/engineering-data/{record}/review/source-pdf', [CentralReviewController::class, 'sourcePdfManufacturer'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.engineering-data.review.source-pdf');

    Route::patch('/admin/manufacturer/engineering-data/{record}/review', [CentralReviewController::class, 'saveManufacturer'])
        ->middleware('workspace:partner')
        ->name('admin.manufacturer.engineering-data.review.save');

    Route::post('/admin/manufacturer/engineering-data/{record}/review/submit', [CentralReviewController::class, 'submitForApproval'])
        ->middleware(['workspace:partner', 'legal.acceptance:manufacturer.portal.access'])
        ->name('admin.manufacturer.engineering-data.review.submit');

    Route::get('/admin/manufacturer/users', [ManufacturerUserController::class, 'index'])
        ->middleware('workspace:manufacturer-admin')
        ->name('admin.manufacturer.users');

    Route::post('/admin/manufacturer/users/invite', [ManufacturerUserController::class, 'invite'])
        ->middleware('workspace:manufacturer-admin')
        ->name('admin.manufacturer.users.invite');

    Route::get('/admin/manufacturer/upgrade', ManufacturerUpgradeController::class)
        ->middleware('workspace:manufacturer-admin')
        ->name('admin.manufacturer.upgrade');

    Route::get('/partner', fn () => redirect()->route('admin.manufacturer'))
        ->middleware('workspace:partner')
        ->name('partner');

    Route::get('/admin/oem', fn () => redirect()->route('admin.manufacturer'))
        ->middleware('workspace:partner')
        ->name('admin.oem');

    Route::get('/admin/platform', PlatformAdminController::class)
        ->middleware('workspace:platform')
        ->name('admin.platform');

    Route::get('/admin/platform/internal-app-access', [InternalAppAccessController::class, 'index'])
        ->middleware('workspace:platform')
        ->name('admin.platform.internal-app-access.index');

    Route::get('/admin/platform/internal-app-access/new', [InternalAppAccessController::class, 'create'])
        ->middleware('workspace:platform')
        ->name('admin.platform.internal-app-access.create');

    Route::post('/admin/platform/internal-app-access', [InternalAppAccessController::class, 'store'])
        ->middleware('workspace:platform')
        ->name('admin.platform.internal-app-access.store');

    Route::get('/admin/platform/internal-app-access/{application}', [InternalAppAccessController::class, 'show'])
        ->middleware('workspace:platform')
        ->name('admin.platform.internal-app-access.show');

    Route::patch('/admin/platform/internal-app-access/{application}', [InternalAppAccessController::class, 'update'])
        ->middleware('workspace:platform')
        ->name('admin.platform.internal-app-access.update');

    Route::post('/admin/platform/internal-app-access/{application}/pause', [InternalAppAccessController::class, 'pause'])
        ->middleware('workspace:platform')
        ->name('admin.platform.internal-app-access.pause');

    Route::post('/admin/platform/internal-app-access/{application}/revoke', [InternalAppAccessController::class, 'revoke'])
        ->middleware('workspace:platform')
        ->name('admin.platform.internal-app-access.revoke');

    Route::post('/admin/platform/internal-app-access/{application}/regenerate-secret', [InternalAppAccessController::class, 'regenerateSecret'])
        ->middleware('workspace:platform')
        ->name('admin.platform.internal-app-access.regenerate-secret');

    Route::get('/admin/platform/services', [PlatformServiceController::class, 'index'])
        ->middleware('workspace:platform')
        ->name('admin.platform.services.index');

    Route::get('/admin/platform/services/{service}', [PlatformServiceController::class, 'show'])
        ->middleware('workspace:platform')
        ->name('admin.platform.services.show');

    Route::post('/admin/platform/services/{service}/pause', [PlatformServiceController::class, 'pause'])
        ->middleware('workspace:platform')
        ->name('admin.platform.services.pause');

    Route::post('/admin/platform/services/{service}/disable', [PlatformServiceController::class, 'disable'])
        ->middleware('workspace:platform')
        ->name('admin.platform.services.disable');

    Route::post('/admin/platform/services/{service}/health-check', [PlatformServiceController::class, 'healthCheck'])
        ->middleware('workspace:platform')
        ->name('admin.platform.services.health-check');

    Route::get('/admin/platform/discovery', [PlatformDiscoveryController::class, 'index'])
        ->middleware('workspace:platform')
        ->name('admin.platform.discovery');

    Route::get('/admin/platform/discovery/{section}', [PlatformDiscoveryController::class, 'section'])
        ->whereIn('section', [
            'landing-pages',
            'metadata',
            'canonical-urls',
            'redirects',
            'structured-data',
            'sitemaps',
            'robots',
            'search-console',
            'ai',
        ])
        ->middleware('workspace:platform')
        ->name('admin.platform.discovery.section');

    Route::post('/admin/platform/discovery/redirects', [PlatformDiscoveryController::class, 'storeRedirect'])
        ->middleware('workspace:platform')
        ->name('admin.platform.discovery.redirects.store');

    Route::patch('/admin/platform/discovery/metadata/{metadata}', [PlatformDiscoveryController::class, 'updateMetadata'])
        ->middleware('workspace:platform')
        ->name('admin.platform.discovery.metadata.update');

    Route::get('/admin/platform/{section}', [PlatformAdminController::class, 'section'])
        ->whereIn('section', [
            'system-health',
            'security',
            'storage',
            'background-jobs',
            'queue-monitor',
            'notifications',
            'logs',
            'backup-recovery',
            'environment',
            'feature-flags',
            'api-keys',
            'internal-app-access',
            'audit-logs',
            'developer-tools',
            'system-administrators',
            'roles',
            'permissions',
            'entitlements',
            'authentication',
            'sso',
            'object-storage',
            'compiler-services',
            'search-index',
            'email',
            'scheduled-jobs',
            'monitoring',
        ])
        ->middleware('workspace:platform')
        ->name('admin.platform.section');

    Route::get('/partner/submissions/new', [UploadController::class, 'createPartner'])
        ->middleware('entitlement:partner.manage_products')
        ->name('partner.submissions.new');

    Route::post('/partner/submissions', [UploadController::class, 'storePartner'])
        ->middleware('entitlement:partner.manage_products')
        ->name('partner.submissions.store');

    Route::get('/partner/submissions/{record}/review', [CentralReviewController::class, 'partner'])
        ->middleware('entitlement:partner.manage_products')
        ->name('partner.submissions.review');

    Route::get('/partner/submissions/{record}/review/source-pdf', [CentralReviewController::class, 'sourcePdfPartner'])
        ->middleware('entitlement:partner.manage_products')
        ->name('partner.submissions.review.source-pdf');

    Route::patch('/partner/submissions/{record}/review', [CentralReviewController::class, 'savePartner'])
        ->middleware('entitlement:partner.manage_products')
        ->name('partner.submissions.review.save');

    Route::get('/compare', CompareController::class)
        ->middleware('entitlement:library.compare')
        ->name('compare');

    Route::get('/compare/export', ComparisonExportController::class)
        ->middleware('entitlement:library.compare')
        ->name('compare.export');

    Route::get('/compare/select', CompareSelectController::class)
        ->middleware('entitlement:library.compare')
        ->name('compare.select');

    Route::get('/records/{record}/export/{format}', EngineeringRecordExportController::class)
        ->whereIn('format', ['datasheet', 'csv', 'pan', 'ond', 'summary-pdf', 'json'])
        ->name('records.export');

    Route::get('/my-library/records/{record}/review', [CentralReviewController::class, 'myLibrary'])
        ->middleware('entitlement:library.private_upload')
        ->name('my-library.records.review');

    Route::get('/my-library/records/{record}/review/source-pdf', [CentralReviewController::class, 'sourcePdfMyLibrary'])
        ->middleware('entitlement:library.private_upload')
        ->name('my-library.records.review.source-pdf');

    Route::patch('/my-library/records/{record}/review', [CentralReviewController::class, 'saveMyLibrary'])
        ->middleware('entitlement:library.private_upload')
        ->name('my-library.records.review.save');

    Route::post('/my-library/records/{record}/review/approve', [CentralReviewController::class, 'approveMyLibrary'])
        ->middleware('entitlement:library.private_upload')
        ->name('my-library.records.review.approve');

    Route::get('/device-scan/uploads/{datasheet}/compiled-records', UploadCompiledRecordsController::class)
        ->name('device-scan.uploads.compiled-records');

    /*
    |--------------------------------------------------------------------------
    | Equipment
    |--------------------------------------------------------------------------
    */
    Route::prefix('library')
        ->name('library.')
        ->group(function () {

            Route::get('/', fn () => redirect()->route('engineering-search'))
                ->name('index');

            /*
        |--------------------------------------------------------------------------
        | Equipment
        |--------------------------------------------------------------------------
        */

            Route::get('/equipment/modules', fn () => redirect()->route('engineering-search', ['device_type' => 'module']))
                ->name('equipment.modules');

            Route::get('/equipment/string-inverters', fn () => redirect()->route('engineering-search', ['device_type' => 'inverter']))
                ->name('equipment.string-inverters');

            Route::get('/equipment/central-inverters', fn () => redirect()->route('engineering-search', ['device_type' => 'inverter']))
                ->name('equipment.central-inverters');

            /*
        |--------------------------------------------------------------------------
        | Import
        |--------------------------------------------------------------------------
        */

            Route::get('/import/module', DashboardRedirectController::class)
                ->name('import.module');

            Route::get('/import/string-inverter', DashboardRedirectController::class)
                ->name('import.string-inverter');

            Route::get('/import/central-inverter', DashboardRedirectController::class)
                ->name('import.central-inverter');

            Route::post('/import', fn () => abort(410, 'Upload is coming in the next milestone.'))
                ->name('import.store');

            Route::get('/review/{deviceType}', fn () => redirect()->route('central-library'))
                ->name('review');

            Route::get('/preview', fn () => abort(410, 'Preview is coming in the next milestone.'))
                ->name('preview');

            Route::get('/preview-image', fn () => abort(410, 'Preview is coming in the next milestone.'))
                ->name('preview-image');

            /*
        |--------------------------------------------------------------------------
        | Export
        |--------------------------------------------------------------------------
        */

            Route::get('/export', DashboardRedirectController::class)
                ->name('export');

            /*
        |--------------------------------------------------------------------------
        | Compare
        |--------------------------------------------------------------------------
        */

            Route::get('/compare', fn () => redirect()->route('compare'))
                ->name('compare');
        });

    /*
    |--------------------------------------------------------------------------
    | DeviceScan compatibility routes
    |--------------------------------------------------------------------------
    | Keep these while controllers/views still reference device-scan.* names.
    */

    Route::get('/device-scan/upload', DashboardRedirectController::class)
        ->name('device-scan.upload');

    Route::post('/device-scan/upload', fn () => abort(410, 'Upload is coming in the next milestone.'))
        ->name('device-scan.upload.store');

    Route::get('/device-scan/review/{deviceType}', fn () => redirect()->route('central-library'))
        ->name('device-scan.review');

    Route::get('/device-scan/preview', fn () => abort(410, 'Preview is coming in the next milestone.'))
        ->name('device-scan.preview');

    Route::get('/device-scan/preview-image', fn () => abort(410, 'Preview is coming in the next milestone.'))
        ->name('device-scan.preview-image');
});

require __DIR__.'/settings.php';
