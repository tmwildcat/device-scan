<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\Models\SeoLandingPage;
use App\Models\SeoMetadata;
use App\Models\SeoRedirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SeoDiscoveryAdminController extends Controller
{
    public function dashboard(): Response
    {
        return Inertia::render('LineWatt/SeoDiscoveryAdmin', [
            'page' => 'dashboard',
            'summary' => $this->summary(),
            'rows' => $this->metadataRows(),
        ]);
    }

    public function page(string $page): Response
    {
        return Inertia::render('LineWatt/SeoDiscoveryAdmin', [
            'page' => $page,
            'summary' => $this->summary(),
            'rows' => match ($page) {
                'redirects' => $this->redirectRows(),
                'landing-pages' => $this->landingRows(),
                default => $this->metadataRows(),
            },
        ]);
    }

    public function storeRedirect(Request $request)
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

    private function summary(): array
    {
        if (! Schema::hasTable('seo_metadata')) {
            return [
                'indexed_pages' => 0,
                'missing_titles' => 0,
                'missing_descriptions' => 0,
                'missing_canonicals' => 0,
                'duplicate_titles' => 0,
                'broken_links' => 0,
                'orphan_pages' => 0,
                'structured_data_coverage' => 0,
            ];
        }

        $total = SeoMetadata::query()->count();

        return [
            'indexed_pages' => SeoMetadata::query()->where('indexable', true)->where('status', 'published')->count(),
            'missing_titles' => SeoMetadata::query()->whereNull('meta_title')->orWhere('meta_title', '')->count(),
            'missing_descriptions' => SeoMetadata::query()->whereNull('meta_description')->orWhere('meta_description', '')->count(),
            'missing_canonicals' => SeoMetadata::query()->whereNull('canonical_path')->orWhere('canonical_path', '')->count(),
            'duplicate_titles' => SeoMetadata::query()
                ->select('meta_title')
                ->whereNotNull('meta_title')
                ->groupBy('meta_title')
                ->havingRaw('COUNT(*) > 1')
                ->count(),
            'broken_links' => 0,
            'orphan_pages' => 0,
            'structured_data_coverage' => $total > 0 ? (int) round((SeoMetadata::query()->where('structured_data_enabled', true)->count() / $total) * 100) : 0,
        ];
    }

    private function metadataRows(): array
    {
        if (! Schema::hasTable('seo_metadata')) {
            return [];
        }

        return SeoMetadata::query()
            ->latest()
            ->limit(40)
            ->get()
            ->map(fn (SeoMetadata $metadata): array => [
                'title' => $metadata->meta_title,
                'kind' => $metadata->entity_kind,
                'path' => $metadata->canonical_path,
                'status' => $metadata->status,
                'indexable' => $metadata->indexable,
                'description' => $metadata->meta_description,
            ])
            ->all();
    }

    private function redirectRows(): array
    {
        if (! Schema::hasTable('seo_redirects')) {
            return [];
        }

        return SeoRedirect::query()
            ->latest()
            ->limit(40)
            ->get()
            ->map(fn (SeoRedirect $redirect): array => [
                'source_path' => $redirect->source_path,
                'target_path' => $redirect->target_path,
                'status_code' => $redirect->status_code,
                'reason' => $redirect->reason,
                'active' => $redirect->active,
            ])
            ->all();
    }

    private function landingRows(): array
    {
        if (! Schema::hasTable('seo_landing_pages')) {
            return [];
        }

        return SeoLandingPage::query()
            ->latest()
            ->limit(40)
            ->get()
            ->map(fn (SeoLandingPage $page): array => [
                'title' => $page->title,
                'kind' => $page->kind,
                'path' => '/'.$page->kind.'/'.$page->slug,
                'status' => $page->status,
                'description' => $page->description,
            ])
            ->all();
    }
}
