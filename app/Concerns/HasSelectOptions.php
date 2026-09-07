<?php

namespace App\Concerns;

/**
 * Exposes a backed enum's cases as value/label pairs for a frontend select.
 *
 * The using enum must be string-backed and define a label() method.
 */
trait HasSelectOptions
{
    /**
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
