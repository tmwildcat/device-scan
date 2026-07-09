<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\LineWattRole;
use App\Models\SeoLandingPage;
use App\Models\SeoMetadata;
use App\Models\SeoRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlatformDiscoveryController extends Controller
{
    private const SECTIONS = [
        'dashboard' => 'Dashboard',
        'landing-pages' => 'Landing Pages',
        'metadata' => 'Metadata',
        'canonical-urls' => 'Canonical URLs',
        'redirects' => 'Redirects',
        'structured-data' => 'Structured Data',
        'sitemaps' => 'Sitemaps',
        'robots' => 'Robots',
        'search-console' => 'Search Console',
        'ai' => 'AI Discoverability',
    ];

    public function index(Request $request): Response
    {
        return $this->render($request, 'dashboard');
    }

    public function section(Request $request, string $section): Response
    {
        abort_unless(array_key_exists($section, self::SECTIONS), 404);

        return $this->render($request, $section);
    }

    public function storeRedirect(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'source_path' => ['required', 'string', 'max:255'],
            'target_path' => ['required', 'string', 'max:255'],
            'status_code' => ['required', 'integer', Rule::in([301, 302])],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        SeoRedirect::query()->updateOrCreate(
            ['source_path' => '/'.ltrim($data['source_path'], '/')],
            [
                'target_path' => '/'.ltrim($data['target_path'], '/'),
                'status_code' => $data['status_code'],
                'reason' => $data['reason'] ?? null,
                'created_by' => $request->user()?->id,
                'active' => true,
            ]
        );

        return back();
    }

    public function updateMetadata(Request $request, SeoMetadata $metadata): RedirectResponse
    {
        $data = $request->validate([
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:2000'],
            'robots' => ['nullable', 'string', 'max:255'],
            'indexable' => ['boolean'],
            'canonical_url' => ['nullable', 'string', 'max:255'],
        ]);

        $metadata->update($data);

        return back();
    }

    private function render(Request $request, string $section): Response
    {
        return Inertia::render('LineWatt/PlatformDiscovery', [
            'workspace' => $this->workspace($request),
            'section' => $section,
            'sectionTitle' => self::SECTIONS[$section],
            'sections' => collect(self::SECTIONS)
                ->map(fn (string $label, string $key) => [
                    'key' => $key,
                    'label' => $label,
                    'href' => $key === 'dashboard' ? '/admin/platform/discovery' : "/admin/platform/discovery/{$key}",
                ])
                ->values()
                ->all(),
            'summary' => $this->summary(),
            'rows' => $this->rows($section),
            'sitemaps' => $section === 'sitemaps' ? $this->sitemaps() : [],
            'robots' => $section === 'robots' ? $this->robotsPreview() : null,
            'structuredData' => $section === 'structured-data' ? $this->structuredDataCoverage() : [],
        ]);
    }

    /**
     * @return array<string,mixed>
     */
    private function workspace(Request $request): array
    {
        return [
            'role_label' => LineWattRole::label($request->user()?->role),
            'environment' => app()->environment(),
            'health' => 'Healthy',
        ];
    }

    /**
     * @return array<string,int|string>
     */
    private function summary(): array
    {
        if (! Schema::hasTable('seo_metadata')) {
            return [
                'public_pages' => 0,
                'indexable_pages' => 0,
                'missing_meta_titles' => 0,
                'missing_meta_descriptions' => 0,
                'missing_canonicals' => 0,
                'duplicate_slugs' => 0,
                'broken_redirects' => 0,
                'structured_data_coverage' => '0%',
                'sitemap_status' => 'Placeholder',
                'orphan_pages' => 'Placeholder',
            ];
        }

        $metadataCount = SeoMetadata::query()->count();
        $landingCount = Schema::hasTable('seo_landing_pages') ? SeoLandingPage::query()->count() : 0;
        $structuredCount = $metadataCount > 0 ? SeoMetadata::query()->where('structured_data_enabled', true)->count() : 0;

        return [
            'public_pages' => $metadataCount + $landingCount,
            'indexable_pages' => SeoMetadata::query()->where('indexable', true)->count(),
            'missing_meta_titles' => SeoMetadata::query()->where(fn ($query) => $query->whereNull('meta_title')->orWhere('meta_title', ''))->count(),
            'missing_meta_descriptions' => SeoMetadata::query()->where(fn ($query) => $query->whereNull('meta_description')->orWhere('meta_description', ''))->count(),
            'missing_canonicals' => SeoMetadata::query()->where(fn ($query) => $query->whereNull('canonical_path')->orWhere('canonical_path', ''))->count(),
            'duplicate_slugs' => $this->duplicateCount('seo_metadata', 'slug') + $this->duplicateCount('seo_landing_pages', 'slug'),
            'broken_redirects' => Schema::hasTable('seo_redirects') ? SeoRedirect::query()->where('active', false)->count() : 0,
            'structured_data_coverage' => $metadataCount > 0 ? ((int) round(($structuredCount / $metadataCount) * 100)).'%' : '0%',
            'sitemap_status' => 'Available',
            'orphan_pages' => 'Placeholder',
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function rows(string $section): array
    {
        return match ($section) {
            'landing-pages' => $this->landingPageRows(),
            'metadata' => $this->metadataAttentionRows(),
            'canonical-urls' => $this->canonicalRows(),
            'redirects' => $this->redirectRows(),
            'structured-data' => $this->structuredDataRows(),
            'sitemaps' => $this->sitemaps(),
            'robots' => [],
            'search-console' => $this->placeholderRows('Search Console is not connected yet.', ['Impressions', 'Clicks', 'CTR', 'Average Position', 'Top Queries', 'Top Pages']),
            'ai' => $this->placeholderRows('AI discoverability is a future capability.', ['AI Sitemaps', 'Structured Engineering Feeds', 'MCP Discoverability', 'Canonical Engineering Records']),
            default => $this->metadataAttentionRows(),
        };
    }

    private function duplicateCount(string $table, string $column): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return DB::table($table)
            ->select($column)
            ->whereNotNull($column)
            ->groupBy($column)
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function landingPageRows(): array
    {
        if (! Schema::hasTable('seo_landing_pages')) {
            return [];
        }

        return SeoLandingPage::query()
            ->latest()
            ->limit(80)
            ->get()
            ->map(fn (SeoLandingPage $page) => [
                'id' => $page->uuid,
                'primary' => $page->title,
                'secondary' => $page->description,
                'type' => $page->kind,
                'slug' => $page->slug,
                'status' => $page->status,
                'indexable' => $page->status === 'published',
                'updated_at' => optional($page->updated_at)->toDateTimeString(),
                'actions' => ['Open', 'Preview', 'Edit Metadata'],
            ])
            ->all();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function metadataAttentionRows(): array
    {
        if (! Schema::hasTable('seo_metadata')) {
            return [];
        }

        return SeoMetadata::query()
            ->where(function ($query): void {
                $query->whereNull('meta_title')
                    ->orWhere('meta_title', '')
                    ->orWhereNull('meta_description')
                    ->orWhere('meta_description', '')
                    ->orWhereNull('og_image')
                    ->orWhere('og_image', '')
                    ->orWhereNull('canonical_path')
                    ->orWhere('canonical_path', '');
            })
            ->latest()
            ->limit(80)
            ->get()
            ->map(fn (SeoMetadata $metadata) => [
                'id' => $metadata->uuid,
                'primary' => $metadata->meta_title ?: $metadata->slug,
                'secondary' => $metadata->meta_description,
                'type' => $metadata->entity_kind,
                'slug' => $metadata->slug,
                'status' => $this->metadataIssue($metadata),
                'indexable' => $metadata->indexable,
                'canonical_url' => $metadata->canonical_url ?: $metadata->canonical_path,
                'updated_at' => optional($metadata->updated_at)->toDateTimeString(),
                'editable' => [
                    'meta_title' => $metadata->meta_title,
                    'meta_description' => $metadata->meta_description,
                    'robots' => $metadata->robots,
                    'indexable' => $metadata->indexable,
                    'canonical_url' => $metadata->canonical_url,
                ],
            ])
            ->all();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function canonicalRows(): array
    {
        if (! Schema::hasTable('seo_metadata')) {
            return [];
        }

        $duplicates = SeoMetadata::query()
            ->select('canonical_url')
            ->whereNotNull('canonical_url')
            ->groupBy('canonical_url')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('canonical_url')
            ->all();

        return SeoMetadata::query()
            ->latest()
            ->limit(80)
            ->get()
            ->map(fn (SeoMetadata $metadata) => [
                'id' => $metadata->uuid,
                'primary' => $metadata->meta_title ?: $metadata->slug,
                'secondary' => $metadata->entity_type,
                'type' => $metadata->entity_kind,
                'slug' => $metadata->slug,
                'status' => $metadata->canonical_path ? 'ok' : 'empty canonical',
                'canonical_url' => $metadata->canonical_url ?: $metadata->canonical_path,
                'conflicts' => in_array($metadata->canonical_url, $duplicates, true) ? 'duplicate canonical' : ($metadata->slug ? null : 'slug missing'),
                'updated_at' => optional($metadata->updated_at)->toDateTimeString(),
            ])
            ->all();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function redirectRows(): array
    {
        if (! Schema::hasTable('seo_redirects')) {
            return [];
        }

        return SeoRedirect::query()
            ->latest()
            ->limit(80)
            ->get()
            ->map(fn (SeoRedirect $redirect) => [
                'id' => $redirect->uuid,
                'primary' => $redirect->source_path,
                'secondary' => $redirect->target_path,
                'type' => (string) $redirect->status_code,
                'status' => $redirect->active ? 'active' : 'inactive',
                'reason' => $redirect->reason,
                'last_hit' => 'Placeholder',
                'updated_at' => optional($redirect->created_at)->toDateTimeString(),
            ])
            ->all();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function structuredDataRows(): array
    {
        return [
            ['primary' => 'Organization', 'type' => 'JSON-LD', 'status' => 'placeholder', 'coverage' => 'Future shared site schema'],
            ['primary' => 'Product', 'type' => 'JSON-LD', 'status' => 'available where metadata exists', 'coverage' => $this->structuredCoverageText()],
            ['primary' => 'Dataset', 'type' => 'JSON-LD', 'status' => 'planned', 'coverage' => 'Engineering Record dataset schema preview'],
            ['primary' => 'Breadcrumb', 'type' => 'JSON-LD', 'status' => 'planned', 'coverage' => 'Public route breadcrumbs'],
            ['primary' => 'WebPage', 'type' => 'JSON-LD', 'status' => 'available where metadata exists', 'coverage' => $this->structuredCoverageText()],
            ['primary' => 'FAQ', 'type' => 'JSON-LD', 'status' => 'placeholder', 'coverage' => 'Knowledge Centre future'],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function structuredDataCoverage(): array
    {
        return $this->structuredDataRows();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function sitemaps(): array
    {
        return [
            ['primary' => 'sitemap.xml', 'type' => 'index', 'status' => 'available', 'url' => '/sitemap.xml', 'url_count' => 'dynamic', 'last_generated' => 'On request'],
            ['primary' => 'manufacturers.xml', 'type' => 'manufacturers', 'status' => 'available', 'url' => '/manufacturers.xml', 'url_count' => 'dynamic', 'last_generated' => 'On request'],
            ['primary' => 'datasheets.xml', 'type' => 'datasheets', 'status' => 'available', 'url' => '/datasheets.xml', 'url_count' => 'dynamic', 'last_generated' => 'On request'],
            ['primary' => 'models.xml', 'type' => 'models', 'status' => 'available', 'url' => '/models.xml', 'url_count' => 'dynamic', 'last_generated' => 'On request'],
            ['primary' => 'technology.xml', 'type' => 'technology', 'status' => 'available', 'url' => '/technology.xml', 'url_count' => 'dynamic', 'last_generated' => 'On request'],
            ['primary' => 'applications.xml', 'type' => 'applications', 'status' => 'available', 'url' => '/applications.xml', 'url_count' => 'dynamic', 'last_generated' => 'On request'],
            ['primary' => 'power-search.xml', 'type' => 'power search', 'status' => 'placeholder', 'url' => null, 'url_count' => 0, 'last_generated' => 'Future'],
            ['primary' => 'knowledge.xml', 'type' => 'knowledge', 'status' => 'future', 'url' => null, 'url_count' => 0, 'last_generated' => 'Future'],
        ];
    }

    private function robotsPreview(): string
    {
        return implode("\n", [
            'User-agent: *',
            'Disallow: /admin/',
            'Disallow: /api/',
            'Disallow: /device-scan/debug/',
            'Allow: /manufacturers/',
            'Allow: /datasheets/',
            'Allow: /models/',
            'Allow: /technology/',
            'Allow: /applications/',
            'Sitemap: '.url('/sitemap.xml'),
        ]);
    }

    private function metadataIssue(SeoMetadata $metadata): string
    {
        if (! $metadata->meta_title) {
            return 'missing title';
        }
        if (! $metadata->meta_description) {
            return 'missing description';
        }
        if (! $metadata->og_image) {
            return 'missing OG image';
        }
        if (! $metadata->canonical_path) {
            return 'missing canonical';
        }

        return 'ok';
    }

    private function structuredCoverageText(): string
    {
        if (! Schema::hasTable('seo_metadata')) {
            return '0 entities';
        }

        return SeoMetadata::query()->where('structured_data_enabled', true)->count().' entities enabled';
    }

    /**
     * @param array<int,string> $items
     * @return array<int,array<string,mixed>>
     */
    private function placeholderRows(string $message, array $items): array
    {
        return collect($items)
            ->map(fn (string $item) => [
                'primary' => $item,
                'type' => 'placeholder',
                'status' => 'not connected',
                'secondary' => $message,
            ])
            ->all();
    }
}
