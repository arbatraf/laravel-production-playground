# Access Control

Laravel policies define role access. Keep role checks out of controllers, resources and views.

## Roles

- `Admin` manages operations records and users, except bulk or hard user deletion.
- `Manager` can read, create and update operations records. No deletes or user management.
- `Viewer` can read operations records. No writes.

## Matrix

| Resource | Admin | Manager | Viewer |
| --- | --- | --- | --- |
| Users | View all, create, update, delete other users | View self | View self |
| Companies | View, create, update, delete, restore, force delete when empty | View, create, update | View |
| Contacts | View, create, update, delete, restore, force delete when no notes exist | View, create, update | View |
| Tasks | View, create, update, delete, restore, force delete when empty | View, create, update | View |
| Notes | View, create, update, delete, restore, force delete | View, create, update own notes | View |

`massDelete` is admin-only for operations records. Users cannot be mass deleted.

Authorize task status changes through the task `update` policy before calling the status action.
