<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;

enum DecisionRelationshipType: string
{
    use HasSelectOptions;

    case Constrains = 'constrains';
    case Supersedes = 'supersedes';
    case RelatedTo = 'related_to';

    public function label(): string
    {
        return match ($this) {
            self::Constrains => 'Constrains',
            self::Supersedes => 'Supersedes',
            self::RelatedTo => 'Related to',
        };
    }
}
