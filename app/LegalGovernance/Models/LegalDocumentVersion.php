<?php

namespace App\LegalGovernance\Models;

use App\LegalGovernance\Enums\LegalVersionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class LegalDocumentVersion extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['status' => LegalVersionStatus::class, 'is_material_change' => 'boolean', 'requires_reacceptance' => 'boolean', 'metadata' => 'array', 'approved_metadata' => 'array', 'proposed_effective_at' => 'immutable_datetime', 'effective_at' => 'immutable_datetime', 'submitted_at' => 'immutable_datetime', 'approved_at' => 'immutable_datetime', 'scheduled_at' => 'immutable_datetime', 'scheduled_publish_at' => 'immutable_datetime', 'schedule_cancelled_at' => 'immutable_datetime', 'published_at' => 'immutable_datetime', 'superseded_at' => 'immutable_datetime', 'withdrawn_at' => 'immutable_datetime', 'archived_at' => 'immutable_datetime'];
    }

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            $original = LegalVersionStatus::from($version->getRawOriginal('status'));
            if ($original->isImmutable()) {
                throw new LogicException('Immutable legal versions cannot be updated. Create a new version.');
            }
            if (in_array($original, [LegalVersionStatus::Approved, LegalVersionStatus::Scheduled], true)
                && $version->isDirty(['markdown_source', 'sanitized_html', 'plain_text', 'content_checksum', 'change_summary', 'is_material_change', 'requires_reacceptance', 'metadata'])) {
                throw new LogicException('Approved legal content and governed metadata are immutable. Return it to Draft first.');
            }
        });
        static::deleting(fn () => throw new LogicException('Legal versions are retention-sensitive and cannot be deleted.'));
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(LegalDocument::class, 'legal_document_id');
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(LegalArtifact::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(LegalReview::class);
    }

    public function placeholders(): HasMany
    {
        return $this->hasMany(LegalPlaceholder::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
