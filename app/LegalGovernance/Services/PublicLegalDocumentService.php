<?php

namespace App\LegalGovernance\Services;

use App\LegalGovernance\Models\LegalDocument;
use App\LegalGovernance\Models\LegalDocumentVersion;
use Illuminate\Support\Collection;

final class PublicLegalDocumentService
{
    public function publicDocument(string $slug, ?string $versionLabel = null): ?array
    {
        $document = LegalDocument::query()->where('application_key', config('legal-governance.application_key'))->where('slug', $slug)->where('visibility', 'public')->where('is_active', true)->first();
        if (! $document) {
            return null;
        }
        $query = $document->versions()->with('artifacts')->where('status', 'published')->whereNotNull('published_at')->where(fn ($builder) => $builder->whereNull('effective_at')->orWhere('effective_at', '<=', now()));
        $version = $versionLabel ? $query->where('version_label', $versionLabel)->first() : $query->latest('effective_at')->latest('published_at')->first();
        if (! $version) {
            return null;
        }

        return $this->present($document, $version);
    }

    public function footerDocuments(): Collection
    {
        return collect(config('legal-governance.public_footer_documents', []))->map(function (array $entry) {
            $resolved = $this->publicDocument($entry['slug']);

            return $resolved ? ['title' => $resolved['title'], 'slug' => $resolved['slug'], 'href' => route('legal.show', ['slug' => $resolved['slug']]), 'required' => (bool) ($entry['required'] ?? false)] : null;
        })->filter()->values();
    }

    public function publicIndex(): Collection
    {
        return LegalDocument::query()->where('application_key', config('legal-governance.application_key'))->where('visibility', 'public')->where('is_active', true)->orderBy('title')->pluck('slug')->map(fn ($slug) => $this->publicDocument($slug))->filter()->values();
    }

    private function present(LegalDocument $document, LegalDocumentVersion $version): array
    {
        return ['title' => $document->title, 'slug' => $document->slug, 'version' => $version->version_label, 'effective_at' => $version->effective_at, 'published_at' => $version->published_at, 'html' => $version->sanitized_html, 'plain_text' => $version->plain_text, 'public_id' => $version->public_id, 'artifacts' => $version->artifacts];
    }
}
