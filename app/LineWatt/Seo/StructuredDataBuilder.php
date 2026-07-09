<?php

namespace App\LineWatt\Seo;

use App\Models\CompiledDeviceRecord;
use App\Models\DeviceDatasheet;
use App\Models\ManufacturerCompany;
use App\Models\SeoMetadata;

class StructuredDataBuilder
{
    public function organization(ManufacturerCompany $manufacturer, SeoMetadata $metadata): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $manufacturer->name,
            'url' => $metadata->canonical_url ?: url($metadata->canonical_path),
            'description' => $metadata->meta_description,
        ];
    }

    public function product(CompiledDeviceRecord $record, SeoMetadata $metadata): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $record->display_name ?: $record->model_name ?: $record->model_series,
            'brand' => $record->manufacturer,
            'category' => $record->device_type,
            'url' => $metadata->canonical_url ?: url($metadata->canonical_path),
            'description' => $metadata->meta_description,
            'additionalProperty' => array_values(array_filter([
                $record->technology ? ['@type' => 'PropertyValue', 'name' => 'Technology', 'value' => $record->technology] : null,
                $record->power_class_w ? ['@type' => 'PropertyValue', 'name' => 'Power Class', 'value' => $record->power_class_w.' W'] : null,
                $record->power_class_kw ? ['@type' => 'PropertyValue', 'name' => 'Power Class', 'value' => $record->power_class_kw.' kW'] : null,
            ])),
        ];
    }

    public function dataset(DeviceDatasheet|CompiledDeviceRecord $entity, SeoMetadata $metadata): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Dataset',
            'name' => $metadata->meta_title,
            'description' => $metadata->meta_description,
            'url' => $metadata->canonical_url ?: url($metadata->canonical_path),
            'creator' => [
                '@type' => 'Organization',
                'name' => 'LineWatt Library',
            ],
            'keywords' => $metadata->meta_keywords,
        ];
    }

    /**
     * @param array<int,array{label:string,url:string}> $items
     */
    public function breadcrumb(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['label'],
                    'item' => $item['url'],
                ],
                $items,
                array_keys($items)
            ),
        ];
    }

    public function webPage(SeoMetadata $metadata): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $metadata->meta_title,
            'description' => $metadata->meta_description,
            'url' => $metadata->canonical_url ?: url($metadata->canonical_path),
        ];
    }

    public function faqPlaceholder(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [],
        ];
    }
}
