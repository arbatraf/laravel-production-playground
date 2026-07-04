# Laravel Production Playground

Laravel 13 portfolio project about business backoffice systems, API design, queues, imports, webhooks, payments, communication workflows, metrics and CI/CD.

![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![MoonShine](https://img.shields.io/badge/MoonShine-4-blue)
![Status](https://img.shields.io/badge/status-foundation-lightgrey)
![License](https://img.shields.io/badge/license-portfolio--only-orange)

> This repository is a technical showcase only.
> It is not a production-ready CRM, SaaS product or commercial application.
> Demo entities and simplified workflows are used instead of real customer data, credentials or commercial logic.

## Status

Foundation stage. Current repository: README, custom portfolio license and roadmap. Laravel code, install steps, demo accounts and screenshots are not in the repo yet.

## About

The project is shaped around day-to-day operational work: companies, contacts, manager tasks, imports, reports, communications, integrations and payments.

It is inspired by e-commerce, product landing-page businesses, lead-based sales operations and CRM-driven workflows.

This is a business-operations demo, not a full CRM, SaaS product, payment system or messenger clone.

Principle: simulated providers are fine; fake architecture is not.

## Stack

| Area | Stack |
| --- | --- |
| Backend | PHP 8.5, Laravel 13, MySQL |
| Backoffice UI | MoonShine 4 |
| Frontend | Blade, Livewire, Alpine.js, Vite |
| Charts | ApexCharts |
| Auth | Laravel Sanctum, policies, gates |
| Async | queues, jobs, events, listeners, scheduler |
| Quality | PHPUnit, Laravel Pint, PHPStan / Larastan, Playwright |
| CI/CD | GitHub Actions |

## Architecture

Business rules stay in Laravel core: models, services, policies, jobs, API resources, events, listeners, console commands and scheduled tasks.

MoonShine 4 stays at the backoffice UI layer: policy-backed resources, forms, filters, query tags, handlers, metrics and operational pages. It keeps routine admin work fast to build while Laravel services, policies and jobs keep the business flow explicit.

Complex workflows stay outside the UI layer.

Livewire fits task-board movement, import progress and the communication center.

Alpine.js covers small local UI interactions.

Delivery is slice-based: each feature combines schema, policy, service/action, API or MoonShine surface, focused tests and a short documentation note before the next feature starts.

## Scope

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

## API

Base path:

```text
/api/v1
```

Initial public endpoints:

```text
GET /api/v1/health
GET /api/v1/health/readiness
```

Protected groups:

```text
/api/v1/companies
/api/v1/tasks
/api/v1/reports
/api/v1/search
/api/v1/mail
/api/v1/discussions
/api/v1/payments
```

Webhook entry points:

```text
POST /api/v1/webhooks/telephony/inbound-call
POST /api/v1/webhooks/messengers/inbound-message
POST /api/v1/webhooks/payments/provider
```

API notes: Sanctum token authentication, `X-Request-Id` correlation, named rate limits, stable JSON errors and OpenAPI documentation.

## Backoffice

Backoffice path:

```text
/backoffice
```

Demo roles:

| Role | Access |
| --- | --- |
| Admin | full demo access |
| Manager | operational records, reports and safe demo actions |
| Viewer | read-only access |

Demo accounts come with seeders once the app is in place.

## Local Development

The Laravel application is not in the repository yet, so there is no runnable setup command at this stage.

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

Setup flow once the app is in place:

```bash
composer install
yarn install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
yarn dev
```

Docker stays optional. Valet and Homebrew remain the primary local path.

## Quality

Checks:

```bash
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
yarn build
yarn test:e2e
```

Coverage:

- services and value objects
- API, policies and webhooks
- imports, payments and communication workflows
- MoonShine resources, policies, handlers, query tags and metrics
- Livewire/Alpine backoffice widgets and key smoke paths
- SDUI structure responses for selected backoffice pages

## Documentation

Long-form notes belong in `docs/`:

- `docs/architecture.md`
- `docs/api.md`
- `docs/openapi.yaml`
- `docs/testing.md`
- `docs/integrations.md`
- `docs/security.md`
- `docs/ci-cd.md`
- `docs/deployment.md`
- `docs/environment.md`
- `docs/demo-data.md`
- `docs/metrics.md`
- `docs/performance.md`
- `docs/adr/*`

Documentation is added with the feature it describes, so the repo does not collect empty architecture files.

## Screenshots

Screenshots come after the first MoonShine pages exist:

- backoffice dashboard
- manager task board
- operations health
- communication center
- payment flow
- API/OpenAPI preview

## Roadmap

| Phase | Scope |
| --- | --- |
| Foundation | README, license, Laravel app, environment example, first ADR, CI baseline |
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
