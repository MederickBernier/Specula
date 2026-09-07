<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;
use App\Contracts\Labelled;

enum FeedType: string implements Labelled
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
