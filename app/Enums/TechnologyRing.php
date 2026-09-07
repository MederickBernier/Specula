<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;
use App\Contracts\Labelled;

/**
 * What you would start something new with today.
 *
 * Deliberately separate from what is actually running: holding a technology
 * says nothing about the three projects still built on it, and the pair of
 * answers is the useful one.
 */
enum TechnologyRing: string implements Labelled
{
    use HasSelectOptions;

    case Adopt = 'adopt';
    case Trial = 'trial';
    case Assess = 'assess';
    case Hold = 'hold';

    public function label(): string
    {
        return match ($this) {
            self::Adopt => 'Adopt',
            self::Trial => 'Trial',
            self::Assess => 'Assess',
            self::Hold => 'Hold',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Adopt => 'Default choice for new work.',
            self::Trial => 'Worth using where the risk is contained.',
            self::Assess => 'Worth understanding, not yet committed to.',
            self::Hold => 'Do not start anything new with this.',
        };
    }
}
