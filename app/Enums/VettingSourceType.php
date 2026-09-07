<?php

namespace App\Enums;

enum VettingSourceType: string
{
    case Meeting = 'meeting';
    case Stakeholder = 'stakeholder';
    case TechRadar = 'tech_radar';
    case SelfInitiated = 'self_initiated';

    public function label(): string
    {
        return match ($this) {
            self::Meeting => 'Meeting',
            self::Stakeholder => 'Stakeholder',
            self::TechRadar => 'Tech radar',
            self::SelfInitiated => 'Self initiated',
        };
    }

    /**
     * Value/label pairs for rendering a select in the frontend.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case): array => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
