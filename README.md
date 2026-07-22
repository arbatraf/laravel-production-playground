# Laravel Production Playground

Laravel 13 portfolio project about business backoffice systems, API design, queues, imports, webhooks, payments, communication workflows, metrics and CI/CD.

![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![MoonShine](https://img.shields.io/badge/MoonShine-4-6B46C1)
![Status](https://img.shields.io/badge/status-backoffice--resources-blue)
![License](https://img.shields.io/badge/license-portfolio--only-orange)

> This repository is a technical showcase only.
> It is not a production-ready CRM, SaaS product or commercial application.
> Demo entities and simplified workflows are used instead of real customer data, credentials or commercial logic.

## Status

Current stage: backoffice resources. Laravel 13/PHP 8.5, MoonShine 4, the CI quality gate and foundation E2E smoke are in place. Policy-backed resources expose users, companies, contacts, tasks, notes and audit events through the backoffice.

Current interfaces: `/`, `/backoffice`, `/api/v1/health`, `/api/v1/health/readiness`.

## About

The project is shaped around day-to-day operational work: companies, contacts, manager tasks, imports, reports, communications, integrations and payments.

Domain context: e-commerce, product landing pages, lead-based sales operations and CRM workflows.

This is a business-operations demo, not a full CRM, SaaS product, payment system or messenger clone.

Principle: simulated providers are fine; fake architecture is not.

## Stack

| Area | Stack |
| --- | --- |
| Backend | PHP 8.5, Laravel 13, MySQL |
| Frontend | Blade, Vite, Yarn |
| Quality | PHPUnit, Node test, Laravel Pint, PHPStan/Larastan level 6 |
| Operations core | User roles, companies, contacts, tasks, notes, audit events, policies, factories, seeders |
| Backoffice | MoonShine 4, branded `/backoffice`, Laravel users and policy-backed resources |
| Planned API/auth | REST API v1, Laravel Sanctum, policies, gates |
| Planned async | queues, jobs, events, listeners, scheduler |
| Planned reporting | ApexCharts, cached metrics, exports |
| Planned delivery | Playwright, release artifacts, deploy dry run |

## Architecture

Business rules stay in Laravel core: models, enums, policies, services, jobs, API resources, events, listeners, console commands and scheduled tasks.

MoonShine 4 stays at the backoffice UI layer. It uses the existing Laravel users and backoffice access gate. Laravel services, policies and jobs own business behavior.

Complex workflows stay outside the UI layer.

Livewire fits task-board movement, import progress and the communication center.

Alpine.js covers small local UI interactions.

Delivery is slice-based: each feature combines schema, policy, service/action, API or MoonShine surface and focused tests. Documentation is added only for new decisions or operational details.

## Planned Scope

| Area | Focus |
| --- | --- |
| Backoffice | branded `/backoffice`, policy-backed MoonShine resources, handlers, query tags, task board, native metrics, SDUI structure demo |
| Operations | users, roles, companies, contacts, manager task board, notes, reports, Laravel import pipeline, audit events |
| API | REST API v1, Sanctum tokens, resources, form requests, pagination, filtering, error format |
| Integrations | signed webhooks, idempotency, provider adapters, retryable jobs, integration logs |
| Communication | domain mail inbox demo, internal discussions, messenger adapter, queued outbound delivery |
| Payments | provider contracts, payment intents, transactions, refunds, signed payment webhooks |
| Metrics | KPI cards, MoonShine index metrics, operations health, import/webhook/payment/communication charts |
| Delivery | GitHub Actions CI, Playwright smoke workflow, release artifact, deploy dry run |

## Planned Interfaces

- Backoffice: `/backoffice`; demo accounts are available only in local and testing environments.
- API: `/api/v1` starts with public health/readiness endpoints. Business endpoints arrive after the API foundation slice.
- Demo users: `admin@example.com`, `manager@example.com`, `viewer@example.com`; password: `password`.

## Local Development

Local setup:

- macOS
- Homebrew
- PHP 8.5
- MySQL
- Laravel Valet
- Yarn
- PhpStorm
- Sequel Ace
- Postman

Setup flow:

```bash
./scripts/composer install
yarn install
cp .env.example .env
./scripts/php artisan key:generate
./scripts/php artisan migrate
yarn dev
```

The default `.env.example` uses MySQL and the local Valet-friendly URL `http://lpp.test`.
Local CLI PHP is pinned to `php@8.5` in `.valetrc`. Use `./scripts/php` and `./scripts/composer`.

Docker stays optional. Valet and Homebrew remain the primary local path.

## Quality

Checks:

```bash
composer run check
yarn build
yarn test:e2e
```

CI runs Composer validation, Pint, PHPStan/Larastan level 6, PHPUnit and `yarn build`.

Playwright will be added when browser smoke coverage lands. Current E2E smoke uses Node test.

Current coverage:

- Laravel foundation page
- application name/configuration
- Laravel health route
- API health/readiness endpoints
- foundation HTTP smoke through Laravel's local server
- operations model: roles, companies, contacts, tasks, notes, policies, factories and seeders
- audit event writer, task status audit, policy access and soft-deleted subject context
- MoonShine login, separate backoffice session, branding and policy-backed resources for users, companies, contacts, tasks, notes and audit events

Planned coverage:

- services and value objects
- API and webhooks
- imports, payments and communication workflows
- MoonShine handlers, query tags and metrics
- Livewire/Alpine backoffice widgets and key smoke paths
- SDUI structure responses for selected backoffice pages

## Documentation

Current docs:

- `docs/architecture.md`
- `docs/access-control.md`
- `docs/audit-events.md`
- `docs/ci-cd.md`
- `docs/demo-data.md`
- `docs/branding.md`
- `docs/testing.md`

Feature docs land with their slices.

## Roadmap

| Phase | Scope |
| --- | --- |
| Foundation | README, license, Laravel app, environment example, CI baseline |
| Operations core | users, roles, companies, contacts, tasks, notes, policies, factories, seeders |
| Backoffice | MoonShine 4 setup, `/backoffice`, policy-backed resources, handlers, query tags, task board, metrics, SDUI structure demo |
| API and access | REST API v1, Sanctum, requests, resources, error format, request IDs, rate limits |
| Webhooks and imports | signed webhooks, idempotency, Laravel import pipeline, row errors, retry/failure visibility |
| Payments and communication | provider contracts, payment intents, refunds, communication center, messenger/mail demos |
| Metrics and delivery | operations health, ApexCharts dashboards, OpenAPI, tests, static analysis, Playwright, dry-run deployment |

## License

Portfolio and demonstration use only.

Commercial use, redistribution, deployment or sublicensing requires written permission from the author.

Full license text: `LICENSE.md`.

## Author

[Arbatraf](https://github.com/arbatraf) — PHP/Laravel developer focused on business backoffice systems, API integrations, operational automation and production workflows.
