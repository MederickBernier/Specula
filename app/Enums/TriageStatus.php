<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;
use App\Contracts\Labelled;

enum TriageStatus: string implements Labelled
{
    use HasSelectOptions;

    case Pending = 'pending';
    case Relevant = 'relevant';
    case Discarded = 'discarded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Relevant => 'Relevant',
            self::Discarded => 'Discarded',
        };
    }
}
