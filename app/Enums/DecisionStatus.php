<?php

namespace App\Enums;

enum DecisionStatus: string
{
    case Draft = 'draft';
    case UnderRework = 'under_rework';
    case Decided = 'decided';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::UnderRework => 'Under rework',
            self::Decided => 'Decided',
            self::Superseded => 'Superseded',
        };
    }
}
