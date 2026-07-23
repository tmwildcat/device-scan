<?php

namespace App\LegalGovernance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalPlaceholder extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['release_blocking' => 'boolean', 'resolved_at' => 'immutable_datetime'];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentVersion::class, 'legal_document_version_id');
    }
}
