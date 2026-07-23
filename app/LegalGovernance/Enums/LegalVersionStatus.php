<?php

namespace App\LegalGovernance\Enums;

enum LegalVersionStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Superseded = 'superseded';
    case Archived = 'archived';
    case Withdrawn = 'withdrawn';

    public function isImmutable(): bool
    {
        return in_array($this, [self::Published, self::Superseded, self::Archived, self::Withdrawn], true);
    }
}
