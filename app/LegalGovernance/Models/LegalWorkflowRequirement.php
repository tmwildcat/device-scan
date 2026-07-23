<?php

namespace App\LegalGovernance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalWorkflowRequirement extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'presentation_rule' => 'array', 'audience_rule' => 'array', 'configuration' => 'array'];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(LegalWorkflow::class, 'legal_workflow_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(LegalDocument::class, 'legal_document_id');
    }
}
