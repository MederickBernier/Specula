<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;

enum PrototypeStatus: string
{
    use HasSelectOptions;

    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Abandoned => 'Abandoned',
        };
    }

    /**
     * Whether the prototype has stopped running, either way it ended.
     *
     * Abandoned is a state of its own rather than a failed result: a spike can
     * die from priority or time without ever being disproven.
     */
    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Abandoned], true);
    }
}
