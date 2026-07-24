# Architecture

Laravel Production Playground is a portfolio Laravel project for business backoffice workflows. It is not a CRM or SaaS product.

The foundation contains Laravel 13, PHP 8.5, MySQL `.env.example`, Vite through Yarn, PHPUnit, Pint, PHPStan/Larastan and GitHub Actions CI.

The operations core contains enum-backed user roles, companies, contacts, tasks, notes, audit events, factories, seeders and Laravel policies.

## Boundaries

Business behavior stays in Laravel code that can be tested without the admin UI:

- models, policies and form requests
- services, actions and value objects where a real boundary exists
- jobs, events and listeners for asynchronous work
- API resources and controllers for REST API surfaces

MoonShine 4 is the backoffice UI layer at `/backoffice`. It uses a separate session guard with the existing `User` model. Admin, Manager and Viewer may enter the panel; resource policies define record access.

Backoffice login does not create persistent remember-me cookies. Password changes invalidate active backoffice sessions. Login, logout, lockout and resource writes create audit events.

MoonShine resources and pages may display data, trigger handlers and call Laravel services, but they must not own business workflows.

Task forms do not change status or completion fields; status transitions stay in `ChangeTaskStatusAction`. Task creators and note authors come from the authenticated backoffice user. Note subjects are selected on creation and are not changed through update forms.

## Operations Core

Roles are stored as `UserRole` enum values on users. Policies own access decisions; role checks should not spread through controllers, resources or views.

Companies, contacts, tasks and notes use soft deletes. Contacts keep archived company context. Tasks keep archived company/contact context. Notes may attach to companies, contacts or tasks.

Task status uses `TaskStatus`. MoonShine handlers validate the task ID and select the target status server-side. `ChangeTaskStatusAction` reloads and locks the actor and task, authorizes the transition, sets `completed_at` for closed statuses and blocks transitions out of them.

Audit events are written through `RecordAuditEventAction`. Backoffice resource writes and their audit rows share a transaction.

Application responses include security headers and an `X-Request-ID`. Valid upstream UUIDs are preserved; other values are replaced. Health is limited to 60 requests per minute per IP. Production readiness is limited to 12, requires a bearer token of at least 32 characters and returns 503 when the token is missing.

## Delivery

Features ship as vertical slices. A slice should include schema, policy, service/action, UI or API entry point, tests and a short documentation note where needed.

Domain modules are added only when a slice has enough behavior to justify the boundary.
