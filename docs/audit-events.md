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

Current recorded event:

| Event | Trigger |
| --- | --- |
| `task.status_changed` | `ChangeTaskStatusAction` changes a task status |

## Data Rules

- Keep `properties` flat and small.
- Store changed values, not full model snapshots.
- Use scalar values only.
- Do not store passwords, tokens, raw provider payloads or full request data.
- Keep `request_id` nullable until request-id middleware exists.
