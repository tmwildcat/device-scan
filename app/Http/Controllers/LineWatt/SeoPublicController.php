<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Seo\SeoUrlBuilder;
use App\LineWatt\Seo\StructuredDataBuilder;
use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\ManufacturerCompany;
use App\Models\SeoLandingPage;
use App\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SeoPublicController extends Controller
{
    public function __construct(
        private readonly SeoUrlBuilder $urls,
        private readonly StructuredDataBuilder $structuredData,
    ) {}

    public function manufacturer(Request $request, string $slug): Response
    {
        $manufacturer = ManufacturerCompany::query()->where('slug', $slug)->firstOrFail();

        return $this->renderEntity($request, $manufacturer);
    }

    public function datasheet(Request $request, string $slug): Response
    {
        $datasheet = $this->entityFromSeo(DeviceDatasheet::class, 'datasheet', $slug)
            ?? DeviceDatasheet::query()
                ->where('status', 'published')
                ->get()
                ->first(fn (DeviceDatasheet $datasheet): bool => $this->urls->slugForEntity($datasheet) === $slug);

        abort_if($datasheet === null, 404);

        return $this->renderEntity($request, $datasheet);
    }

    public function model(Request $request, string $slug): Response
    {
        $record = $this->entityFromSeo(CompiledDeviceRecord::class, 'model', $slug)
            ?? CompiledDeviceRecord::query()
                ->where('status', 'published')
                ->get()
                ->first(fn (CompiledDeviceRecord $record): bool => $this->urls->slugForEntity($record) === $slug);

        abort_if($record === null, 404);

        return $this->renderEntity($request, $record);
    }

    public function landing(Request $request, string $slug): Response
    {
        $kind = (string) $request->route('kind');
        $page = SeoLandingPage::query()
            ->where('slug', $slug)
            ->whereIn('kind', [$kind, str($kind)->singular()->toString()])
            ->first();

        if ($page === null) {
            $page = new SeoLandingPage([
                'kind' => str($kind)->singular()->toString(),
                'title' => str($slug)->replace('-', ' ')->title()->toString(),
                'slug' => $slug,
                'description' => 'LineWatt Library landing page for renewable energy engineering discovery.',
                'status' => 'draft',
            ]);
        }

        return $this->renderEntity($request, $page);
    }

    private function renderEntity(Request $request, Model $entity): Response
    {
        $metadata = $entity->exists
            ? $this->urls->metadataFor($entity, 'en')
            : $this->draftMetadata($entity);

        return Inertia::render('LineWatt/SeoPublicPage', [
            'entity' => $this->entityPayload($entity),
            'seo' => $this->metadataPayload($metadata),
            'structuredData' => $this->structuredDataPayload($entity, $metadata),
            'internalLinks' => $this->internalLinks($entity),
        ]);
    }

    private function entityFromSeo(string $class, string $kind, string $slug): ?Model
    {
        $metadata = SeoMetadata::query()
            ->where('entity_type', $class)
            ->where('entity_kind', $kind)
            ->where('slug', $slug)
            ->first();

        if ($metadata === null || $metadata->entity_id === null) {
            return null;
        }

        return $class::query()->find($metadata->entity_id);
    }

    private function draftMetadata(Model $entity): SeoMetadata
    {
        $kind = $entity instanceof SeoLandingPage ? $entity->kind : $this->urls->kindFor($entity);
        $slug = $entity instanceof SeoLandingPage ? $entity->slug : $this->urls->slugForEntity($entity);
        $path = $this->urls->canonicalPath($kind, $slug);

        return new SeoMetadata([
            'entity_kind' => $kind,
            'slug' => $slug,
            'canonical_path' => $path,
            'canonical_url' => url($path),
            'meta_title' => ($entity instanceof SeoLandingPage ? $entity->title : $this->urls->labelFor($entity)).' | LineWatt Library',
            'meta_description' => $entity instanceof SeoLandingPage ? $entity->description : 'LineWatt Library renewable energy engineering data.',
            'robots' => 'index,follow',
            'structured_data_enabled' => true,
            'indexable' => true,
            'priority' => 0.5,
            'change_frequency' => 'monthly',
            'status' => $entity instanceof SeoLandingPage ? $entity->status : 'draft',
        ]);
    }

    private function metadataPayload(SeoMetadata $metadata): array
    {
        return [
            'title' => $metadata->meta_title,
            'description' => $metadata->meta_description,
            'keywords' => $metadata->meta_keywords,
            'canonical_url' => $metadata->canonical_url ?: url($metadata->canonical_path),
            'canonical_path' => $metadata->canonical_path,
            'robots' => $metadata->robots,
            'og_title' => $metadata->og_title ?: $metadata->meta_title,
            'og_description' => $metadata->og_description ?: $metadata->meta_description,
            'og_image' => $metadata->og_image,
            'twitter_title' => $metadata->twitter_title ?: $metadata->meta_title,
            'twitter_description' => $metadata->twitter_description ?: $metadata->meta_description,
            'twitter_image' => $metadata->twitter_image,
            'status' => $metadata->status,
        ];
    }

    private function entityPayload(Model $entity): array
    {
        return [
            'kind' => $entity instanceof SeoLandingPage ? $entity->kind : $this->urls->kindFor($entity),
            'title' => $this->urls->labelFor($entity),
            'description' => $entity instanceof SeoLandingPage ? $entity->description : null,
            'manufacturer' => $entity->manufacturer ?? $entity->name ?? null,
            'device_type' => $entity->device_type ?? null,
            'technology' => $entity->technology ?? null,
            'status' => $entity->status ?? null,
        ];
    }

    private function structuredDataPayload(Model $entity, SeoMetadata $metadata): array
    {
        $items = [
            'web_page' => $this->structuredData->webPage($metadata),
            'breadcrumb' => $this->structuredData->breadcrumb([
                ['label' => 'LineWatt Library', 'url' => url('/')],
                ['label' => $this->urls->labelFor($entity), 'url' => $metadata->canonical_url ?: url($metadata->canonical_path)],
            ]),
        ];

        if ($entity instanceof ManufacturerCompany) {
            $items['organization'] = $this->structuredData->organization($entity, $metadata);
        }

        if ($entity instanceof CompiledDeviceRecord) {
            $items['product'] = $this->structuredData->product($entity, $metadata);
            $items['dataset'] = $this->structuredData->dataset($entity, $metadata);
        }

        if ($entity instanceof DeviceDatasheet) {
            $items['dataset'] = $this->structuredData->dataset($entity, $metadata);
        }

        return $items;
    }

    private function internalLinks(Model $entity): array
    {
        return [
            ['label' => 'Manufacturers', 'href' => '/manufacturers'],
            ['label' => 'Module Search', 'href' => '/search/modules'],
            ['label' => 'Inverter Search', 'href' => '/search/inverters'],
            ['label' => 'Technology Pages', 'href' => '/technology/topcon-modules'],
            ['label' => 'Applications', 'href' => '/applications/commercial-rooftop'],
        ];
    }
}
