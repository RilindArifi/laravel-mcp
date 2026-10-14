<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Blocked = 'blocked';
    case Done = 'done';

    /**
     * Get the human readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Todo => 'To do',
            self::InProgress => 'In progress',
            self::Blocked => 'Blocked',
            self::Done => 'Done',
        };
    }

    /**
     * Determine if the status represents work that is not finished.
     */
    public function isOpen(): bool
    {
        return $this !== self::Done;
    }

    /**
     * Get the statuses as select options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }

    /**
     * Get every status value.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get the status values that represent unfinished work.
     *
     * @return array<int, string>
     */
    public static function openValues(): array
    {
        return array_column(
            array_filter(self::cases(), fn (self $status): bool => $status->isOpen()),
            'value',
        );
    }
}
