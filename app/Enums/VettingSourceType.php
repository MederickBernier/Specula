<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;
use App\Contracts\Labelled;

enum VettingSourceType: string implements Labelled
{
    use HasSelectOptions;

    case Meeting = 'meeting';
    case Stakeholder = 'stakeholder';
    case TechRadar = 'tech_radar';
    case SelfInitiated = 'self_initiated';

    public function label(): string
    {
        return match ($this) {
            self::Meeting => 'Meeting',
            self::Stakeholder => 'Stakeholder',
            self::TechRadar => 'Tech radar',
            self::SelfInitiated => 'Self initiated',
        };
    }
}
