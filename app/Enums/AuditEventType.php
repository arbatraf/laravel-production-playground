<?php

namespace App\Enums;

enum AuditEventType: string
{
    case TaskStatusChanged = 'task.status_changed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
