<?php

namespace App\LineWatt\Seo;

use App\Models\SeoMetadata;
use Illuminate\Support\Collection;

class SitemapBuilder
{
    /**
     * @return Collection<int,array{loc:string,lastmod:string,changefreq:string,priority:string}>
     */
    public function entries(?string $kind = null): Collection
    {
        return SeoMetadata::query()
            ->where('indexable', true)
            ->where('status', 'published')
            ->when($kind, fn ($query) => $query->where('entity_kind', $kind))
            ->orderBy('canonical_path')
            ->get()
            ->map(fn (SeoMetadata $metadata): array => [
                'loc' => $metadata->canonical_url ?: url($metadata->canonical_path),
                'lastmod' => $metadata->updated_at?->toAtomString() ?? now()->toAtomString(),
                'changefreq' => $metadata->change_frequency ?: 'weekly',
                'priority' => number_format((float) $metadata->priority, 1),
            ]);
    }

    public function urlSet(?string $kind = null): string
    {
        $urls = $this->entries($kind)->map(fn (array $entry): string => sprintf(
            "  <url>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n    <changefreq>%s</changefreq>\n    <priority>%s</priority>\n  </url>",
            e($entry['loc']),
            e($entry['lastmod']),
            e($entry['changefreq']),
            e($entry['priority'])
        ))->implode("\n");

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
            "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n".
            $urls."\n".
            "</urlset>\n";
    }

    public function index(): string
    {
        $maps = [
            'manufacturers.xml',
            'datasheets.xml',
            'models.xml',
            'technology.xml',
            'applications.xml',
        ];

        $items = collect($maps)->map(fn (string $map): string => sprintf(
            "  <sitemap>\n    <loc>%s</loc>\n    <lastmod>%s</lastmod>\n  </sitemap>",
            e(url('/'.$map)),
            e(now()->toAtomString())
        ))->implode("\n");

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".
            "<sitemapindex xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n".
            $items."\n".
            "</sitemapindex>\n";
    }
}
