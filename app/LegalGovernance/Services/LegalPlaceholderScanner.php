<?php

namespace App\LegalGovernance\Services;

use App\LegalGovernance\Models\LegalDocumentVersion;

final class LegalPlaceholderScanner
{
    /** @return list<array{placeholder:string,context:string,release_blocking:bool}> */
    public function scan(string $text): array
    {
        $found = [];
        $approved = config('legal-governance.approved_runtime_placeholders', []);
        foreach (config('legal-governance.placeholder_patterns', []) as $pattern) {
            preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches[0] ?? [] as [$placeholder,$offset]) {
                $found[$placeholder.'@'.$offset] = ['placeholder' => $placeholder, 'context' => trim(substr($text, max(0, $offset - 80), strlen($placeholder) + 160)), 'release_blocking' => ! in_array($placeholder, $approved, true)];
            }
        }

        return array_values($found);
    }

    public function persist(LegalDocumentVersion $version): int
    {
        $version->placeholders()->delete();
        foreach ($this->scan($version->markdown_source) as $item) {
            $version->placeholders()->create([...$item, 'severity' => 'error', 'status' => 'open']);
        }

        return $version->placeholders()->count();
    }
}
