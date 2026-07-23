<?php

namespace App\LegalGovernance\Actions;

use App\LegalGovernance\Support\CanonicalJson;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;

final class GenerateRepositoryExportBundle
{
    public function handle(iterable $versions, string $actor): string
    {
        $id = (string) Str::uuid();
        $prefix = config('legal-governance.storage_prefix').'/exports/'.$id;
        $checks = [];
        $manifest = [];
        foreach ($versions as $version) {
            if (! in_array($version->status->value, ['approved', 'published'], true)) {
                throw new LogicException('Only Approved or Published versions may be exported.');
            }$version->loadMissing('document');
            $source = $version->document->source_path ?: 'docs/legal/'.$version->document->slug.'.md';
            $path = $prefix.'/'.$source;
            Storage::disk(config('legal-governance.storage_disk'))->put($path, $version->markdown_source);
            $checks[$source] = hash('sha256', $version->markdown_source);
            $manifest[] = ['slug' => $version->document->slug, 'version' => $version->version_label, 'path' => $source, 'checksum' => $checks[$source]];
        }Storage::disk(config('legal-governance.storage_disk'))->put($prefix.'/manifest.json', CanonicalJson::encode(['generated_by' => $actor, 'documents' => $manifest]));
        Storage::disk(config('legal-governance.storage_disk'))->put($prefix.'/checksums.json', CanonicalJson::encode($checks));
        Storage::disk(config('legal-governance.storage_disk'))->put($prefix.'/CHANGELOG.md', "# Legal export\n\nGenerated repository-ready bundle {$id}.\n");

        return $prefix;
    }
}
