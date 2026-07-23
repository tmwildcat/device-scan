<?php

namespace App\LegalGovernance\Actions;

use App\LegalGovernance\Models\LegalDocument;
use App\LegalGovernance\Models\LegalDocumentVersion;
use App\LegalGovernance\Services\LegalContentRenderer;
use App\LegalGovernance\Services\LegalPlaceholderScanner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

final class ImportLegalMarkdown
{
    public function __construct(private LegalContentRenderer $renderer, private LegalPlaceholderScanner $placeholders) {}

    /** @return array{created:int,unchanged:int,conflicts:int,documents:list<string>} */
    public function import(?string $manifestPath = null, ?string $actor = null): array
    {
        $path = $manifestPath ?: config('legal-governance.source_manifest');
        if (! is_file($path)) {
            throw new RuntimeException("Legal manifest not found: {$path}");
        }
        $result = ['created' => 0, 'unchanged' => 0, 'conflicts' => 0, 'documents' => []];
        foreach ($this->rows(file($path, FILE_IGNORE_NEW_LINES) ?: []) as $row) {
            if (($row['Import'] ?? '') !== 'yes') {
                continue;
            }
            $source = base_path(trim($row['Source path'], '`'));
            if (! is_file($source)) {
                throw new RuntimeException("Legal source not found: {$source}");
            }
            $markdown = file_get_contents($source);
            $rendered = $this->renderer->render($markdown);
            DB::transaction(function () use ($row, $source, $markdown, $rendered, $actor, &$result): void {
                $doc = LegalDocument::query()->firstOrCreate(['application_key' => $row['Application key'], 'slug' => $row['Slug']], ['public_id' => (string) Str::uuid(), 'title' => $row['Title'], 'description' => null, 'document_type' => $row['Type'], 'category' => $row['Category'], 'visibility' => $row['Visibility'], 'default_locale' => 'en', 'source_path' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $source), 'is_active' => true, 'requires_acceptance_default' => ! in_array($row['Default acceptance'], ['acknowledgement'], true), 'metadata' => ['default_acceptance_type' => $row['Default acceptance']]]);
                $existing = $doc->versions()->where('version_label', '1.0 Draft')->where('locale', 'en')->whereNull('jurisdiction')->first();
                if ($existing && hash_equals($existing->content_checksum, $rendered['checksum'])) {
                    $result['unchanged']++;

                    return;
                }
                if ($existing) {
                    $existing->update(['metadata' => [...($existing->metadata ?? []), 'filesystem_reconciliation' => ['source_checksum' => $rendered['checksum'], 'detected_at' => now()->toIso8601String()]]]);
                    $result['conflicts']++;

                    return;
                }
                $version = LegalDocumentVersion::create(['public_id' => (string) Str::uuid(), 'legal_document_id' => $doc->id, 'version_label' => '1.0 Draft', 'locale' => 'en', 'jurisdiction' => null, 'status' => 'draft', 'markdown_source' => $markdown, 'sanitized_html' => $rendered['html'], 'plain_text' => $rendered['plain_text'], 'content_checksum' => $rendered['checksum'], 'change_summary' => 'Imported from governed Markdown source.', 'created_by' => $actor, 'updated_by' => $actor, 'source_import_reference' => $doc->source_path, 'metadata' => ['imported_at' => now()->toIso8601String()]]);
                $this->placeholders->persist($version);
                $result['created']++;
                $result['documents'][] = $doc->slug;
            });
        }

        return $result;
    }

    /** @return iterable<array<string,string>> */
    private function rows(array $lines): iterable
    {
        $headers = null;
        foreach ($lines as $line) {
            if (! str_starts_with(trim($line), '|')) {
                continue;
            }
            $cells = array_map('trim', explode('|', trim(trim($line), '|')));
            if ($headers === null && in_array('Source path', $cells, true)) {
                $headers = $cells;

                continue;
            }
            if ($headers === null || preg_match('/^-+$/', $cells[0] ?? '')) {
                continue;
            }
            if (count($cells) !== count($headers)) {
                continue;
            }
            yield array_combine($headers, $cells);
        }
    }
}
