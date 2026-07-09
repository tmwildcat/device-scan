<?php

namespace App\LineWatt\Seo;

use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\ManufacturerCompany;
use App\Models\PowerSearchOption;
use App\Models\SeoLandingPage;
use App\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SeoUrlBuilder
{
    /**
     * @var array<string,string>
     */
    private array $prefixes = [
        'manufacturer' => 'manufacturers',
        'datasheet' => 'datasheets',
        'model' => 'models',
        'technology' => 'technology',
        'application' => 'applications',
        'power_search' => 'search',
        'comparison' => 'compare',
        'knowledge' => 'learn',
    ];

    public function slug(string $value): string
    {
        $slug = Str::slug(trim($value));

        return $slug !== '' ? $slug : 'linewatt-record';
    }

    public function canonicalPath(string $kind, string $slug): string
    {
        $prefix = $this->prefixes[$kind] ?? Str::plural(Str::slug($kind));

        return '/'.$prefix.'/'.$this->slug($slug);
    }

    public function canonicalUrl(string $kind, string $slug): string
    {
        return url($this->canonicalPath($kind, $slug));
    }

    public function kindFor(Model|string $entity): string
    {
        $class = is_string($entity) ? $entity : $entity::class;

        return match ($class) {
            ManufacturerCompany::class => 'manufacturer',
            DeviceDatasheet::class => 'datasheet',
            CompiledDeviceRecord::class => 'model',
            PowerSearchOption::class => 'power_search',
            SeoLandingPage::class => 'technology',
            default => 'web_page',
        };
    }

    public function labelFor(Model $entity): string
    {
        if ($entity instanceof ManufacturerCompany) {
            return $entity->name;
        }

        if ($entity instanceof DeviceDatasheet) {
            return $entity->product_name ?: $entity->series ?: $entity->datasheet_original_filename ?: 'Datasheet';
        }

        if ($entity instanceof CompiledDeviceRecord) {
            return $entity->display_name ?: $entity->model_name ?: $entity->model_series ?: 'Engineering Model';
        }

        if ($entity instanceof PowerSearchOption) {
            return $entity->label ?? $entity->name ?? 'Power Search';
        }

        if ($entity instanceof SeoLandingPage) {
            return $entity->title;
        }

        return class_basename($entity);
    }

    public function slugForEntity(Model $entity): string
    {
        if ($entity instanceof ManufacturerCompany && $entity->slug) {
            return $entity->slug;
        }

        if ($entity instanceof CompiledDeviceRecord) {
            return $this->slug(implode(' ', array_filter([
                $entity->manufacturer,
                $entity->display_name ?: $entity->model_name ?: $entity->model_series,
                $entity->power_class_w ? ((string) $entity->power_class_w).'w' : null,
                $entity->power_class_kw ? ((string) $entity->power_class_kw).'kw' : null,
            ])));
        }

        if ($entity instanceof DeviceDatasheet) {
            return $this->slug(implode(' ', array_filter([
                $entity->manufacturer,
                $entity->product_name ?: $entity->series ?: $entity->datasheet_original_filename,
                $entity->metadata['revision'] ?? null,
            ])));
        }

        return $this->slug($this->labelFor($entity));
    }

    public function metadataFor(Model $entity, string $locale = 'en'): SeoMetadata
    {
        $kind = $this->kindFor($entity);
        $slug = $this->slugForEntity($entity);
        $canonicalPath = $this->canonicalPath($kind, $slug);

        return SeoMetadata::query()->firstOrCreate(
            [
                'entity_type' => $entity::class,
                'entity_id' => $entity->getKey(),
                'locale' => $locale,
            ],
            [
                'entity_kind' => $kind,
                'slug' => $slug,
                'canonical_path' => $canonicalPath,
                'canonical_url' => url($canonicalPath),
                'meta_title' => $this->defaultTitle($entity),
                'meta_description' => $this->defaultDescription($entity),
                'og_title' => $this->defaultTitle($entity),
                'og_description' => $this->defaultDescription($entity),
                'twitter_title' => $this->defaultTitle($entity),
                'twitter_description' => $this->defaultDescription($entity),
                'status' => $this->defaultStatus($entity),
                'priority' => $kind === 'manufacturer' ? 0.8 : 0.6,
                'change_frequency' => $kind === 'manufacturer' ? 'weekly' : 'monthly',
            ]
        );
    }

    private function defaultTitle(Model $entity): string
    {
        return match (true) {
            $entity instanceof ManufacturerCompany => "{$entity->name} Engineering Data | LineWatt Library",
            $entity instanceof DeviceDatasheet => trim(($entity->manufacturer ? "{$entity->manufacturer} " : '').($entity->product_name ?: $entity->series ?: 'Datasheet')).' | LineWatt Library',
            $entity instanceof CompiledDeviceRecord => trim(($entity->manufacturer ? "{$entity->manufacturer} " : '').($entity->display_name ?: $entity->model_name ?: $entity->model_series ?: 'Engineering Record')).' Specifications | LineWatt Library',
            default => $this->labelFor($entity).' | LineWatt Library',
        };
    }

    private function defaultDescription(Model $entity): string
    {
        return match (true) {
            $entity instanceof ManufacturerCompany => "Browse {$entity->name} renewable energy equipment, datasheets and structured engineering records in LineWatt Library.",
            $entity instanceof DeviceDatasheet => "Source datasheet metadata, revision and engineering compilation status for ".($entity->manufacturer ?: 'renewable energy equipment').'.',
            $entity instanceof CompiledDeviceRecord => "Structured engineering specifications for ".($entity->display_name ?: $entity->model_name ?: $entity->model_series ?: 'a renewable energy product').'.',
            default => 'LineWatt Library renewable energy engineering data.',
        };
    }

    private function defaultStatus(Model $entity): string
    {
        if ($entity instanceof DeviceDatasheet || $entity instanceof CompiledDeviceRecord) {
            return $entity->status === 'published' ? 'published' : 'draft';
        }

        return 'published';
    }
}
