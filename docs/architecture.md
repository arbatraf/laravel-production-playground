# Architecture

Laravel Production Playground is a portfolio Laravel project for business backoffice workflows. It is not a CRM or SaaS product.

The foundation contains Laravel 13, PHP 8.5, MySQL `.env.example`, Vite through Yarn, PHPUnit, Pint, PHPStan/Larastan and GitHub Actions CI.

The operations core contains enum-backed user roles, companies, contacts, factories, seeders and Laravel policies.

## Boundaries

Business behavior stays in Laravel code that can be tested without the admin UI:

- models, policies and form requests
- services, actions and value objects where a real boundary exists
- jobs, events and listeners for asynchronous work
- API resources and controllers for REST API surfaces

MoonShine 4 will be the backoffice UI layer. Resources and pages may display data, trigger handlers and call Laravel services, but they must not own business workflows.

## Operations Core

Roles are stored as `UserRole` enum values on users. Policies own access decisions; role checks should not spread through controllers, resources or views.

Companies and contacts use soft deletes. Contacts keep their company relation available when a company is archived.

## Delivery

Features ship as vertical slices. A slice should include schema, policy, service/action, UI or API entry point, tests and a short documentation note where needed.

Domain modules are added only when a slice has enough behavior to justify the boundary.
