<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;
use App\Contracts\Labelled;

enum VettingStatus: string implements Labelled
{
    use HasSelectOptions;

    case New = 'new';
    case InProgress = 'in_progress';
    case Vetted = 'vetted';
    case NeedsPrototype = 'needs_prototype';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InProgress => 'In progress',
            self::Vetted => 'Vetted',
            self::NeedsPrototype => 'Needs prototype',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * Whether the item has reached the end of its intake lifecycle.
     *
     * NeedsPrototype is deliberately not resolved: the proposal is still open,
     * it is just waiting on a prototype before it can be vetted or rejected.
     */
    public function isResolved(): bool
    {
        return in_array($this, [self::Vetted, self::Rejected], true);
    }
}
