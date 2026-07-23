<?php

namespace App\LegalGovernance\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class LegalAcceptance extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['evidence' => 'array', 'accepted_at' => 'immutable_datetime', 'declined_at' => 'immutable_datetime', 'withdrawn_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Legal acceptances are append-only.'));
        static::deleting(fn () => throw new LogicException('Legal acceptances are append-only.'));
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
