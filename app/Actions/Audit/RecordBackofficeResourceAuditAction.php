<?php

namespace App\Actions\Audit;

use App\Enums\AuditEventType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final readonly class RecordBackofficeResourceAuditAction
{
    public function __construct(
        private RecordAuditEventAction $recordAuditEvent,
    ) {}

    public function created(Model $subject, User $user, ?string $requestId): void
    {
        ($this->recordAuditEvent)(
            eventType: AuditEventType::ResourceCreated,
            description: sprintf('%s created.', class_basename($subject)),
            user: $user,
            subject: $subject,
            requestId: $requestId,
        );
    }

    public function updated(Model $subject, User $user, ?string $requestId): void
    {
        $changedFields = array_values(array_diff(
            array_keys($subject->getChanges()),
            ['updated_at'],
        ));

        if ($changedFields === []) {
            return;
        }

        $properties = [
            'changed_fields' => implode(',', $changedFields),
        ];

        if ($subject instanceof User) {
            if (in_array('role', $changedFields, true)) {
                $properties['from_role'] = $this->roleValue($subject->getPrevious()['role'] ?? null);
                $properties['to_role'] = $this->roleValue($subject->getChanges()['role'] ?? null);
            }

            if (in_array('password', $changedFields, true)) {
                $properties['credentials_changed'] = true;
            }
        }

        ($this->recordAuditEvent)(
            eventType: AuditEventType::ResourceUpdated,
            description: sprintf('%s updated.', class_basename($subject)),
            user: $user,
            subject: $subject,
            properties: $properties,
            requestId: $requestId,
        );
    }

    public function deleted(Model $subject, User $user, ?string $requestId): void
    {
        ($this->recordAuditEvent)(
            eventType: AuditEventType::ResourceDeleted,
            description: sprintf('%s deleted.', class_basename($subject)),
            user: $user,
            subject: $subject,
            requestId: $requestId,
        );
    }

    private function roleValue(mixed $role): ?string
    {
        if ($role instanceof UserRole) {
            return $role->value;
        }

        return is_string($role) ? $role : null;
    }
}
