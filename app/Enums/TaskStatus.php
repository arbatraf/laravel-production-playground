<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Done = 'done';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In progress',
            self::Waiting => 'Waiting',
            self::Done => 'Done',
            self::Canceled => 'Canceled',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Done, self::Canceled], true);
    }

    public function canTransitionTo(self $status): bool
    {
        if ($this === $status) {
            return true;
        }

        return match ($this) {
            self::Open => in_array($status, [self::InProgress, self::Waiting, self::Done, self::Canceled], true),
            self::InProgress => in_array($status, [self::Waiting, self::Done, self::Canceled], true),
            self::Waiting => in_array($status, [self::Open, self::InProgress, self::Canceled], true),
            self::Done, self::Canceled => false,
        };
    }
}
