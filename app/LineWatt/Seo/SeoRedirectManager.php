<?php

namespace App\LineWatt\Seo;

use App\Models\SeoRedirect;
use App\Models\User;

class SeoRedirectManager
{
    public function create(string $sourcePath, string $targetPath, string $reason, int $statusCode = 301, ?User $actor = null): SeoRedirect
    {
        $sourcePath = '/'.ltrim($sourcePath, '/');
        $targetPath = '/'.ltrim($targetPath, '/');

        return SeoRedirect::query()->updateOrCreate(
            ['source_path' => $sourcePath],
            [
                'target_path' => $targetPath,
                'status_code' => in_array($statusCode, [301, 302], true) ? $statusCode : 301,
                'reason' => $reason,
                'created_by' => $actor?->id,
                'active' => true,
            ]
        );
    }
}
