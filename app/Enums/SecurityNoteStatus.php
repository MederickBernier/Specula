<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;

enum SecurityNoteStatus: string
{
    use HasSelectOptions;

    case Flagged = 'flagged';
    case Routed = 'routed';
    case Remediated = 'remediated';
    case Deferred = 'deferred';
    case NonIssue = 'non_issue';

    public function label(): string
    {
        return match ($this) {
            self::Flagged => 'Flagged',
            self::Routed => 'Routed',
            self::Remediated => 'Remediated',
            self::Deferred => 'Deferred',
            self::NonIssue => 'Non-issue',
        };
    }

    /**
     * Whether the finding is closed.
     *
     * NonIssue is kept distinct from Remediated so that "closed, nothing to
     * fix" never lands in a remediation count. Deferred stays open: the work
     * was postponed, not finished.
     */
    public function isResolved(): bool
    {
        return in_array($this, [self::Remediated, self::NonIssue], true);
    }
}
