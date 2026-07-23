<?php

namespace App\Console\Commands;

use App\LegalGovernance\Models\LegalDocument;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Services\LegalPublicationReadiness;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class ValidateLegalDocumentsCommand extends Command
{
    protected $signature = 'legal:validate-documents {--manifest=} {--production : Fail when required public footer documents are not publication-ready}';

    protected $description = 'Report legal corpus structure, metadata, links, placeholders and manifest consistency';

    public function handle(): int
    {
        $manifest = (string) ($this->option('manifest') ?: config('legal-governance.source_manifest'));
        $errors = [];
        $warnings = [];
        $rows = $this->manifestRows($manifest);
        $metadataRows = $this->workflowMetadataRows($manifest);
        $metadataBySlug = collect($metadataRows)->keyBy('Slug');
        $slugs = [];
        foreach ($rows as $row) {
            $path = base_path(trim($row['Source path'] ?? '', '`'));
            $slug = $row['Slug'] ?? '';
            if ($slug === '' || isset($slugs[$slug])) {
                $errors[] = "Duplicate or missing manifest slug: {$slug}";
            }
            $slugs[$slug] = true;
            $metadata = $metadataBySlug->get($slug);
            if (! $metadata) {
                $errors[] = "Missing per-document workflow metadata: {$slug}";
            } else {
                foreach (['Audience', 'Requires acceptance', 'Workflow triggers', 'Re-accept on Material Change', 'Publication status', 'Contains placeholders', 'Release blockers', 'Related documents', 'Precedence', 'Legal review'] as $field) {
                    if (blank($metadata[$field] ?? null)) {
                        $errors[] = "{$slug} missing manifest field: {$field}";
                    }
                }
                if (($metadata['Publication status'] ?? null) !== 'draft' || ($metadata['Legal review'] ?? null) !== 'pending') {
                    $errors[] = "{$slug} manifest metadata must remain draft and pending legal review.";
                }
            }
            if (! is_file($path)) {
                $errors[] = "Missing manifest source: {$path}";

                continue;
            }
            $text = (string) file_get_contents($path);
            foreach (['**Version:** 1.0 Draft', '**Status:** Draft for Legal Review'] as $metadata) {
                if (! str_contains($text, $metadata)) {
                    $errors[] = basename($path)." missing {$metadata}";
                }
            }
            if (str_contains($text, '**Effective Date:**') || preg_match('/\*\*Status:\*\*\s+(Approved|Published)/', $text)) {
                $errors[] = basename($path).' appears operative.';
            }
            preg_match_all('/\[[^\]]+\]\(([^)]+)\)/', $text, $links);
            foreach ($links[1] ?? [] as $link) {
                if (str_starts_with($link, '#') || preg_match('/^[a-z]+:/i', $link)) {
                    continue;
                }
                $target = dirname($path).'/'.urldecode(explode('#', $link)[0]);
                if (! is_file($target) && ! is_dir($target)) {
                    $errors[] = basename($path)." has broken relative link: {$link}";
                }
            }
            preg_match_all('/\[[A-Z][A-Z0-9 _\/.-]{2,}\]/', $text, $placeholders);
            if (($placeholders[0] ?? []) !== []) {
                $warnings[] = basename($path).': '.count($placeholders[0]).' unresolved placeholders';
            }
        }
        foreach (['PRIVACY_POLICY.md', 'GDPR_PRIVACY_NOTICE.md', 'COOKIE_POLICY.md', 'DATA_PROCESSING_ADDENDUM.md', 'AI_PROCESSING_POLICY.md', 'DOCUMENT_RETENTION_POLICY.md', 'SECURITY_POLICY.md', 'SUBPROCESSORS.md', 'DATA_RETENTION_SCHEDULE.md', 'COMPLIANCE_MATRIX.md'] as $required) {
            if (! collect($rows)->contains(fn ($row) => str_ends_with(trim($row['Source path'] ?? '', '`'), $required))) {
                $errors[] = "Required privacy/GDPR document absent from manifest: {$required}";
            }
        }
        foreach ($metadataRows as $metadata) {
            if (! isset($slugs[$metadata['Slug'] ?? ''])) {
                $errors[] = 'Workflow metadata references unknown manifest slug: '.($metadata['Slug'] ?? '(missing)');
            }
        }
        $this->validateRoleConfiguration($errors);
        $this->validatePublicFooter($errors, $warnings);
        $this->table(['Result', 'Count'], [['Manifest entries', count($rows)], ['Errors', count($errors)], ['Warnings', count($warnings)]]);
        foreach ($errors as $error) {
            $this->error($error);
        }
        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        return $errors === [] ? self::SUCCESS : self::FAILURE;
    }

    /** @param list<string> $errors */
    private function validateRoleConfiguration(array &$errors): void
    {
        $publisher = config('legal-governance.role_permissions.legal_publisher', []);
        $counsel = config('legal-governance.role_permissions.legal_counsel', []);
        foreach (['legal.dashboard.view', 'legal.documents.view', 'legal.documents.create', 'legal.documents.edit', 'legal.versions.create', 'legal.versions.submit_review', 'legal.reviews.view'] as $permission) {
            if (! in_array($permission, $publisher, true)) {
                $errors[] = "Legal Publisher is missing required permission: {$permission}.";
            }
        }
        foreach (['legal.versions.approve', 'legal.versions.publish', 'legal.versions.schedule', 'legal.versions.withdraw', 'legal.versions.archive'] as $permission) {
            if (in_array($permission, $publisher, true)) {
                $errors[] = "Legal Publisher has prohibited permission: {$permission}.";
            }
            if (! in_array($permission, $counsel, true)) {
                $errors[] = "Legal Counsel is missing required permission: {$permission}.";
            }
        }
    }

    /** @param list<string> $errors @param list<string> $warnings */
    private function validatePublicFooter(array &$errors, array &$warnings): void
    {
        $production = (bool) $this->option('production') || app()->environment('production');
        if (! $production) {
            return;
        }

        if (! Schema::hasTable('legal_documents') || ! Schema::hasTable('legal_document_versions')) {
            $errors[] = 'Public footer validation unavailable: legal governance tables do not exist.';

            return;
        }

        $this->validateLifecycleEvidence($errors);

        foreach (config('legal-governance.public_footer_documents', []) as $entry) {
            $slug = (string) ($entry['slug'] ?? '');
            $required = (bool) ($entry['required'] ?? false);
            $document = LegalDocument::query()
                ->where('application_key', config('legal-governance.application_key'))
                ->where('slug', $slug)
                ->first();
            $reason = null;

            if (! $document) {
                $reason = 'document is absent from the legal registry';
            } elseif ($document->visibility !== 'public') {
                $reason = "visibility is {$document->visibility}, not public";
            } elseif (! $document->is_active) {
                $reason = 'document is inactive';
            } else {
                $published = $document->versions()->where('status', 'published')->whereNotNull('published_at')->latest('published_at')->first();
                if (! $published) {
                    $latestStatus = $document->versions()->latest('created_at')->first()?->status?->value;
                    $reason = $latestStatus ? "no published version exists (latest status: {$latestStatus})" : 'no version exists';
                } elseif ($published->effective_at?->isFuture()) {
                    $reason = 'published version is not yet effective (effective '.$published->effective_at->toIso8601String().')';
                }
            }

            if ($reason === null) {
                continue;
            }

            $message = "Public footer excludes {$slug}: {$reason}.";
            if ($required) {
                $errors[] = $message;
            } else {
                $warnings[] = $message;
            }
        }
    }

    /** @param list<string> $errors */
    private function validateLifecycleEvidence(array &$errors): void
    {
        LegalDocumentVersion::query()->with('reviews', 'document', 'placeholders')->each(function (LegalDocumentVersion $version) use (&$errors): void {
            $status = $version->status->value;
            if ($status === 'in_review' && (! $version->submitted_at || ! $version->submitted_for_review_by || $version->reviews->isEmpty())) {
                $errors[] = "{$version->document->slug} {$version->version_label}: submitted version lacks submission or review evidence.";
            }
            if (in_array($status, ['approved', 'scheduled', 'published', 'superseded', 'withdrawn'], true)
                && (! $version->approved_at || ! $version->approved_by || ! $version->approved_checksum)) {
                $errors[] = "{$version->document->slug} {$version->version_label}: {$status} version lacks approval evidence.";
            }
            if (in_array($status, ['approved', 'scheduled'], true) && $version->approved_checksum && ! hash_equals($version->approved_checksum, $version->content_checksum)) {
                $errors[] = "{$version->document->slug} {$version->version_label}: approved content checksum changed.";
            }
            if ($status === 'scheduled' && (! $version->scheduled_publish_at || ! $version->scheduled_publish_at->isFuture() || ! $version->scheduled_by)) {
                $errors[] = "{$version->document->slug} {$version->version_label}: schedule evidence is missing or not future-dated.";
            }
            if ($status === 'published') {
                foreach (app(LegalPublicationReadiness::class)->failures($version) as $failure) {
                    $errors[] = "{$version->document->slug} {$version->version_label}: published readiness failure: {$failure}";
                }
            }
        });

        LegalDocumentVersion::query()->where('status', 'published')->selectRaw('legal_document_id, count(*) as aggregate')->groupBy('legal_document_id')->havingRaw('count(*) > 1')->get()->each(function ($row) use (&$errors): void {
            $errors[] = "Document {$row->legal_document_id} has multiple current Published versions.";
        });
    }

    /** @return list<array<string,string>> */
    private function manifestRows(string $path): array
    {
        $headers = null;
        $rows = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (! str_starts_with(trim($line), '|')) {
                continue;
            }
            $cells = array_map('trim', explode('|', trim(trim($line), '|')));
            if ($headers === null && in_array('Source path', $cells, true)) {
                $headers = $cells;

                continue;
            }
            if ($headers && count($cells) === count($headers) && ! Str::of($cells[0])->remove('-')->isEmpty()) {
                $rows[] = array_combine($headers, $cells);
            }
        }

        return $rows;
    }

    /** @return list<array<string,string>> */
    private function workflowMetadataRows(string $path): array
    {
        $headers = null;
        $rows = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if (! str_starts_with(trim($line), '|')) {
                continue;
            }
            $cells = array_map('trim', explode('|', trim(trim($line), '|')));
            if ($headers === null && in_array('Workflow triggers', $cells, true)) {
                $headers = $cells;

                continue;
            }
            if ($headers && count($cells) === count($headers) && ! Str::of($cells[0])->remove('-')->isEmpty()) {
                $rows[] = array_combine($headers, $cells);
            }
        }

        return $rows;
    }
}
