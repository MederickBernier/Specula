<?php

namespace App\Enums;

enum VettingStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Vetted = 'vetted';
    case NeedsPrototype = 'needs_prototype';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::InProgress => 'In progress',
            self::Vetted => 'Vetted',
            self::NeedsPrototype => 'Needs prototype',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * Whether the item has reached the end of its intake lifecycle.
     *
     * NeedsPrototype is deliberately not resolved: the proposal is still open,
     * it is just waiting on a prototype before it can be vetted or rejected.
     */
    public function isResolved(): bool
    {
        return in_array($this, [self::Vetted, self::Rejected], true);
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
