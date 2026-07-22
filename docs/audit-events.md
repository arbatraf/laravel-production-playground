# Audit Events

Audit events keep a short history of operations changes for support and admin review.

## Fields

| Field | Meaning |
| --- | --- |
| `user_id` | Actor, nullable for system events |
| `event_type` | Stable event name, for example `task.status_changed` |
| `subject_type`, `subject_id` | Optional polymorphic target |
| `description` | Short event summary |
| `properties` | Small JSON diff or context |
| `request_id` | Optional request correlation id |

## Writer

Use `RecordAuditEventAction` for writes. Do not create audit rows through mass assignment in controllers, resources or seeders.

Recorded events:

| Event | Trigger |
| --- | --- |
| `task.status_changed` | `ChangeTaskStatusAction` changes a task status |
| `backoffice.login` | A user signs in through the backoffice guard |
| `backoffice.logout` | A user signs out from the backoffice guard |
| `backoffice.locked_out` | Backoffice authentication is rate limited |
| `resource.created` | A backoffice resource creates a model |
| `resource.updated` | A backoffice resource changes model fields |
| `resource.deleted` | A backoffice resource deletes or archives a model |

Backoffice resource writes and audit rows share a transaction. Updates store changed field names only. Role changes store `from_role` and `to_role`; password changes store `credentials_changed: true`.

## Data Rules

- Keep `properties` flat and small.
- Store small scalar context, not full model snapshots.
- Use scalar values only.
- Do not store passwords, tokens, raw provider payloads or full request data.
- Preserve a valid upstream UUID request ID or generate one; return it in the response and attach it to relevant audit rows.
