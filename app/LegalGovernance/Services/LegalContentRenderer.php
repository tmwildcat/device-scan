<?php

namespace App\LegalGovernance\Services;

use Illuminate\Support\Str;

final class LegalContentRenderer
{
    /** @return array{html:string,plain_text:string,checksum:string} */
    public function render(string $markdown): array
    {
        $html = Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);
        $plain = trim(html_entity_decode(strip_tags(preg_replace('/<\/(p|h[1-6]|li|tr)>/i', "$0\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return ['html' => $html, 'plain_text' => $plain, 'checksum' => hash(config('legal-governance.checksum_algorithm', 'sha256'), $markdown)];
    }
}
