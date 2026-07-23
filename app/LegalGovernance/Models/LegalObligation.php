<?php

namespace App\LegalGovernance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalObligation extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['required_at' => 'immutable_datetime', 'due_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime', 'waived_at' => 'immutable_datetime', 'metadata' => 'array'];
    }

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(LegalDocumentVersion::class, 'legal_document_version_id');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(LegalWorkflow::class, 'legal_workflow_id');
    }
}
