<?php

namespace App\Enums;

enum DecisionStatus: string
{
    case Draft = 'draft';
    case UnderRework = 'under_rework';
    case Decided = 'decided';
    case Superseded = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::UnderRework => 'Under rework',
            self::Decided => 'Decided',
            self::Superseded => 'Superseded',
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
