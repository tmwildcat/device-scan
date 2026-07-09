<?php

use App\LineWatt\Access\LineWattRole;
use App\LineWatt\Seo\SeoUrlBuilder;
use App\LineWatt\Seo\StructuredDataBuilder;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\ManufacturerCompany;
use App\Models\SeoLandingPage;
use App\Models\SeoMetadata;
use App\Models\SeoRedirect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('generates clean canonical slugs and URLs without exposing database IDs', function () {
    $urls = app(SeoUrlBuilder::class);

    expect($urls->slug('Jinko Solar / Tiger Neo 610W'))->toBe('jinko-solar-tiger-neo-610w')
        ->and($urls->canonicalPath('model', 'JKM595N-78HL4'))->toBe('/models/jkm595n-78hl4')
        ->and($urls->canonicalUrl('manufacturer', 'Vikram Solar'))->toEndWith('/manufacturers/vikram-solar');
});

it('persists SEO metadata for public model records', function () {
    $record = seoRecord();

    $metadata = app(SeoUrlBuilder::class)->metadataFor($record);

    expect($metadata)->toBeInstanceOf(SeoMetadata::class)
        ->and($metadata->entity_kind)->toBe('model')
        ->and($metadata->canonical_path)->toStartWith('/models/')
        ->and($metadata->meta_title)->toContain('LineWatt Library');

    $this->assertDatabaseHas('seo_metadata', [
        'entity_type' => CompiledDeviceRecord::class,
        'entity_id' => $record->id,
        'entity_kind' => 'model',
    ]);
});

it('serves canonical model pages with JSON-LD payloads', function () {
    $record = seoRecord();
    $metadata = app(SeoUrlBuilder::class)->metadataFor($record);

    $this->get($metadata->canonical_path)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/SeoPublicPage')
            ->where('seo.canonical_path', $metadata->canonical_path)
            ->where('entity.kind', 'model')
            ->has('structuredData.product')
            ->has('structuredData.dataset')
            ->has('structuredData.breadcrumb')
        );
});

it('generates sitemap XML from published SEO metadata', function () {
    $record = seoRecord();
    $metadata = app(SeoUrlBuilder::class)->metadataFor($record);
    $metadata->forceFill(['status' => 'published', 'indexable' => true])->save();

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('/models.xml', false);

    $this->get('/models.xml')
        ->assertOk()
        ->assertSee($metadata->canonical_url, false);
});

it('creates and applies redirects when canonical paths change', function () {
    $record = seoRecord();
    $metadata = app(SeoUrlBuilder::class)->metadataFor($record);
    $oldPath = $metadata->canonical_path;

    $metadata->forceFill([
        'slug' => 'new-jinko-model',
        'canonical_path' => '/models/new-jinko-model',
        'canonical_url' => url('/models/new-jinko-model'),
    ])->save();

    $this->assertDatabaseHas('seo_redirects', [
        'source_path' => $oldPath,
        'target_path' => '/models/new-jinko-model',
        'status_code' => 301,
    ]);

    $this->get($oldPath)->assertRedirect('/models/new-jinko-model');
});

it('builds supported structured data types', function () {
    $record = seoRecord();
    $metadata = app(SeoUrlBuilder::class)->metadataFor($record);
    $builder = app(StructuredDataBuilder::class);

    expect($builder->product($record, $metadata)['@type'])->toBe('Product')
        ->and($builder->dataset($record, $metadata)['@type'])->toBe('Dataset')
        ->and($builder->webPage($metadata)['@type'])->toBe('WebPage')
        ->and($builder->breadcrumb([['label' => 'Home', 'url' => url('/')]])['@type'])->toBe('BreadcrumbList')
        ->and($builder->faqPlaceholder()['@type'])->toBe('FAQPage');
});

it('exposes the business discovery admin module to admin users', function () {
    $admin = User::factory()->create([
        'role' => LineWattRole::ADMIN,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get('/admin/business/discovery')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/SeoDiscoveryAdmin')
            ->where('page', 'dashboard')
        );

    $this->actingAs($admin)
        ->post('/admin/business/discovery/redirects', [
            'source_path' => '/old-vikram',
            'target_path' => '/manufacturers/vikram-solar',
            'status_code' => 301,
            'reason' => 'Manufacturer renamed',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('seo_redirects', [
        'source_path' => '/old-vikram',
        'target_path' => '/manufacturers/vikram-solar',
    ]);
});

it('serves taxonomy landing pages without hardcoding every power search URL', function () {
    SeoLandingPage::create([
        'kind' => 'technology',
        'title' => 'TOPCon Modules',
        'slug' => 'topcon-modules',
        'description' => 'TOPCon module landing page.',
        'status' => 'published',
    ]);

    $this->get('/technology/topcon-modules')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LineWatt/SeoPublicPage')
            ->where('entity.kind', 'technology')
            ->where('entity.title', 'TOPCon Modules')
        );
});

function seoRecord(): CompiledDeviceRecord
{
    $datasheet = DeviceDatasheet::create([
        'uuid' => (string) Str::uuid(),
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'series' => 'Tiger Neo',
        'product_name' => 'Tiger Neo Datasheet',
        'status' => 'published',
        'datasheet_disk' => 'local',
        'datasheet_path' => 'seo/test.pdf',
        'datasheet_sha256' => hash('sha256', 'seo'),
    ]);

    return CompiledDeviceRecord::create([
        'uuid' => (string) Str::uuid(),
        'device_datasheet_id' => $datasheet->id,
        'source_type' => 'central_curated',
        'device_type' => 'module',
        'manufacturer' => 'Jinko Solar',
        'series' => 'Tiger Neo',
        'technology' => 'TOPCon',
        'model_series' => 'JKM-N-78HL4',
        'model_name' => 'JKM610N-78HL4',
        'display_name' => 'JKM610N-78HL4',
        'power_class_w' => 610,
        'status' => 'published',
        'compiled_disk' => 'local',
        'compiled_path' => 'seo/test.json',
        'compiled_sha256' => hash('sha256', 'compiled'),
    ]);
}
