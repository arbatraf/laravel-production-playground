# Access Control

Laravel policies define role access. Keep role checks out of controllers, resources and views.

Admin, Manager and Viewer may enter `/backoffice`. Resource access remains policy-backed.

MoonShine navigation follows each resource's `viewAny` ability. Hidden menu items do not replace route authorization.

## Roles

- `Admin` manages operations records and users, except bulk or hard user deletion.
- `Manager` can read, create and update operations records. No deletes or user management.
- `Viewer` can read operations records. No writes.

## Matrix

| Resource | Admin | Manager | Viewer |
| --- | --- | --- | --- |
| Users | View all, create, update, delete other users | View self | View self |
| Companies | View, create, update, delete, restore, force delete when no contacts, tasks or notes exist | View, create, update | View |
| Contacts | View, create, update, delete, restore, force delete when no notes exist | View, create, update | View |
| Tasks | View, create, update, delete, restore, force delete when no notes exist | View, create, update | View |
| Notes | View, create, update, delete, restore, force delete | View, create, update own notes | View |
| Audit events | View | No access | No access |

Manager and Viewer retain the domain-level ability to view themselves, but the MoonShine user resource is unavailable because `viewAny` is admin-only.

`massDelete` is admin-only for operations records, limited to 100 records per request and uses the standard resource delete and audit path. Users cannot be mass deleted.

User writes run through MoonShine save and destroy handlers. The final `Admin` cannot be demoted or deleted. Transactions lock the fresh actor and target; self-demotion also locks one remaining administrator.
The handlers authorize the fresh actor and target through `UserPolicy`. Direct model, Builder and raw SQL mutations are outside this application boundary and must not be used for production runtime user writes; seeders and migrations remain explicit maintenance paths.

Authorize task status changes through the task `update` policy before calling the status action.
