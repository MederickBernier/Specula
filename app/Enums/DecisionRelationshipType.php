<?php

namespace App\Enums;

enum DecisionRelationshipType: string
{
    case Constrains = 'constrains';
    case Supersedes = 'supersedes';
    case RelatedTo = 'relatedTo';

    public function label(): string
    {
        return match ($this) {
            self::Constrains => 'Constrains',
            self::Supersedes => 'Supersedes',
            self::RelatedTo => 'Related to',
        };
    }
}
