<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;

enum ItemLinkType: string
{
    use HasSelectOptions;

    case SpawnedFrom = 'spawned_from';
    case ResultedIn = 'resulted_in';
    case RelatedTo = 'related_to';

    public function label(): string
    {
        return match ($this) {
            self::SpawnedFrom => 'Spawned from',
            self::ResultedIn => 'Resulted in',
            self::RelatedTo => 'Related to',
        };
    }
}
