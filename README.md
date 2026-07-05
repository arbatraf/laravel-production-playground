# Laravel Production Playground

Laravel 13 portfolio project about business backoffice systems, API design, queues, imports, webhooks, payments, communication workflows, metrics and CI/CD.

![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![MoonShine](https://img.shields.io/badge/MoonShine-4-6B46C1)
![Status](https://img.shields.io/badge/status-foundation-lightgrey)
![License](https://img.shields.io/badge/license-portfolio--only-orange)

> This repository is a technical showcase only.
> It is not a production-ready CRM, SaaS product or commercial application.
> Demo entities and simplified workflows are used instead of real customer data, credentials or commercial logic.

## Status

Foundation stage. Installed now: Laravel 13, PHP 8.5, Vite, Yarn, PHPUnit, Pint, PHPStan/Larastan and GitHub Actions CI. Current routes: `GET /`, `GET /up`, `GET /api/v1/health`, `GET /api/v1/health/readiness`.

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
| Quality | PHPUnit, Laravel Pint, PHPStan/Larastan level 6 |
| Planned backoffice | MoonShine 4, Livewire, Alpine.js |
| Planned API/auth | REST API v1, Laravel Sanctum, policies, gates |
| Planned async | queues, jobs, events, listeners, scheduler |
| Planned reporting | ApexCharts, cached metrics, exports |
| Planned delivery | Playwright, release artifacts, deploy dry run |

## Architecture

Business rules stay in Laravel core: models, services, policies, jobs, API resources, events, listeners, console commands and scheduled tasks.

MoonShine 4 will stay at the backoffice UI layer: policy-backed resources, forms, filters, query tags, handlers, metrics and operational pages. Laravel services, policies and jobs own business behavior.

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

- Backoffice: `/backoffice` after the MoonShine 4 slice.
- API: `/api/v1` starts with public health/readiness endpoints. Business endpoints arrive after the API foundation slice.
- Demo users: Admin, Manager and Viewer after the operations core slice.

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
composer install
yarn install
cp .env.example .env
php artisan key:generate
php artisan migrate
yarn dev
```

The default `.env.example` uses MySQL and the local Valet-friendly URL `http://lpp.test`.

Docker stays optional. Valet and Homebrew remain the primary local path.

## Quality

Checks:

```bash
composer run check
yarn build
```

CI runs Composer validation, Pint, PHPStan/Larastan level 6, PHPUnit and `yarn build`.

Playwright will be added when browser smoke coverage lands.

Current coverage:

- Laravel foundation page
- application name/configuration
- Laravel health route
- API health/readiness endpoints

Planned coverage:

- services and value objects
- API, policies and webhooks
- imports, payments and communication workflows
- MoonShine resources, policies, handlers, query tags and metrics
- Livewire/Alpine backoffice widgets and key smoke paths
- SDUI structure responses for selected backoffice pages

## Documentation

Current docs:

- `docs/architecture.md`
- `docs/ci-cd.md`
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
