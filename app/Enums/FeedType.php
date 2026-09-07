<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;

enum FeedType: string
{
    use HasSelectOptions;

    case Rss = 'rss';
    case Atom = 'atom';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Rss => 'RSS',
            self::Atom => 'Atom',
            self::Other => 'Other',
        };
    }
}
