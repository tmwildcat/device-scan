<?php

namespace App\LegalGovernance\Services;

use App\LegalGovernance\Contracts\LegalPdfRendererContract;
use App\LegalGovernance\Models\LegalDocumentVersion;
use Illuminate\Support\Facades\Storage;
use LogicException;

final class LegalArtifactService
{
    public function __construct(private ?LegalPdfRendererContract $pdf = null) {}

    public function generate(LegalDocumentVersion $version, ?string $actor = null): void
    {
        $version->loadMissing('document');
        $disk = config('legal-governance.storage_disk', 'local');
        $base = trim(config('legal-governance.storage_prefix', 'legal-governance'), '/').'/'.$version->public_id;
        $items = ['markdown' => [$version->markdown_source, 'text/markdown', 'database-frozen', '1'], 'html' => [$version->sanitized_html, 'text/html', 'laravel-commonmark', app()->version()], 'plain_text' => [$version->plain_text, 'text/plain', 'legal-content-renderer', '1']];
        $json = json_encode(['document' => $version->document->slug, 'version' => $version->version_label, 'checksum' => $version->content_checksum], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $items['json'] = [$json, 'application/json', 'legal-artifact-service', '1'];
        if ($this->pdf && in_array('pdf', config('legal-governance.enabled_artifacts', []), true)) {
            $items['pdf'] = [$this->pdf->render($version->document->title, $version->plain_text), 'application/pdf', $this->pdf->name(), $this->pdf->version()];
        }
        foreach ($items as $type => [$bytes,$mime,$renderer,$rendererVersion]) {
            if (! in_array($type, config('legal-governance.enabled_artifacts', []), true)) {
                continue;
            }
            $path = $base.'/'.$type.'.'.match ($type) {
                'markdown' => 'md','plain_text' => 'txt','html' => 'html','json' => 'json','pdf' => 'pdf'
            };
            if (Storage::disk($disk)->exists($path) && $version->artifacts()->where('artifact_type', $type)->exists()) {
                throw new LogicException('Frozen legal artifact already exists.');
            }
            Storage::disk($disk)->put($path, $bytes);
            $version->artifacts()->create(['artifact_type' => $type, 'storage_disk' => $disk, 'storage_path' => $path, 'mime_type' => $mime, 'byte_size' => strlen($bytes), 'checksum_algorithm' => 'sha256', 'checksum' => hash('sha256', $bytes), 'renderer_name' => $renderer, 'renderer_version' => $rendererVersion, 'generated_at' => now(), 'generated_by' => $actor, 'metadata' => []]);
        }
    }
}
