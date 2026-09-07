<?php

namespace App\Enums;

enum DecisionRelationshipType: string
{
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
