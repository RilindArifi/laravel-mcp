<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    /**
     * Get the human readable label for the priority.
     */
    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Urgent => 'Urgent',
        };
    }

    /**
     * Get the sorting weight of the priority, highest first.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Urgent => 4,
            self::High => 3,
            self::Medium => 2,
            self::Low => 1,
        };
    }

    /**
     * Get the priorities as select options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $priority): array => ['value' => $priority->value, 'label' => $priority->label()],
            self::cases(),
        );
    }

    /**
     * Get every priority value.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
