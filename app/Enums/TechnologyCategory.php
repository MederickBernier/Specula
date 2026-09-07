<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;
use App\Contracts\Labelled;

/**
 * Broad enough to sort an inventory, narrow enough that a thing has one obvious
 * home. The split that matters to a lead is what a piece of technology is for,
 * not what it is written in.
 */
enum TechnologyCategory: string implements Labelled
{
    use HasSelectOptions;

    case Language = 'language';
    case Framework = 'framework';
    case Datastore = 'datastore';
    case Platform = 'platform';
    case Infrastructure = 'infrastructure';
    case Library = 'library';
    case Service = 'service';
    case Tooling = 'tooling';
    case Protocol = 'protocol';

    public function label(): string
    {
        return match ($this) {
            self::Language => 'Language',
            self::Framework => 'Framework',
            self::Datastore => 'Datastore',
            self::Platform => 'Platform',
            self::Infrastructure => 'Infrastructure',
            self::Library => 'Library',
            self::Service => 'Service',
            self::Tooling => 'Tooling',
            self::Protocol => 'Protocol',
        };
    }
}
