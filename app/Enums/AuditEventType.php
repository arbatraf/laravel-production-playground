<?php

namespace App\Enums;

enum AuditEventType: string
{
    case BackofficeLockedOut = 'backoffice.locked_out';
    case BackofficeLogin = 'backoffice.login';
    case BackofficeLogout = 'backoffice.logout';
    case ResourceCreated = 'resource.created';
    case ResourceDeleted = 'resource.deleted';
    case ResourceUpdated = 'resource.updated';
    case TaskStatusChanged = 'task.status_changed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
