<?php

namespace App\LegalGovernance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalArtifact extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['generated_at' => 'immutable_datetime', 'metadata' => 'array'];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentVersion::class, 'legal_document_version_id');
    }
}
