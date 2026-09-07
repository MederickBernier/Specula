<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;
use App\Contracts\Labelled;

/**
 * What is actually running, as opposed to what is recommended.
 */
enum TechnologyStatus: string implements Labelled
{
    use HasSelectOptions;

    case Current = 'current';
    case Deprecated = 'deprecated';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Current => 'Current',
            self::Deprecated => 'Deprecated',
            self::Retired => 'Retired',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Current => 'In use and supported.',
            self::Deprecated => 'Still running, on the way out.',
            self::Retired => 'No longer running anywhere.',
        };
    }
}
