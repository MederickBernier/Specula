<?php

namespace App\Enums;

use App\Concerns\HasSelectOptions;

enum ConfidenceLevel: string
{
    use HasSelectOptions;

    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
        };
    }
}
