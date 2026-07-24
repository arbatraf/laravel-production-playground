# Testing

The foundation uses PHPUnit, Laravel Pint and PHPStan/Larastan.

## Current Checks

```bash
composer run check
yarn build
```

Targeted commands:

```bash
composer run composer:validate
composer run architecture
composer run test
composer run pint:test
composer run analyse
yarn test:e2e
```

`phpunit.xml` targets the isolated MySQL database `laravel_production_playground_testing` and defaults local runs to the database-scoped `lpp_test` user. The base test case rejects any other database before migrations run. CI supplies a temporary MySQL database with the same name.

The browser test wrapper clears cached configuration and verifies the effective local MySQL connection and selected schema before running `migrate:fresh`.

Composer scripts use `./scripts/php`, so Laravel tools run on PHP 8.5 even when global CLI PHP differs.

## Current Coverage

- public foundation page loads
- application name matches the project name
- Laravel health route is available
- health and readiness: infrastructure status, bearer authorization, fail-closed readiness and rate limits
- response middleware: security headers, request IDs and the HSTS environment boundary
- architecture guard suite covers Yarn commands, MoonShine 4 namespaces and debug helpers in production paths
- foundation E2E smoke covers the home page and API health route through Laravel's local server
- operations domain tests cover role casts, company/contact/task/note relations, task status transitions, audit events, soft deletes, policy matrix and deterministic seeders
- backoffice: login/logout auditing, separate session guard, password revocation, role access and branding
- MoonShine resources: registration, policy enforcement, role-based page access, menu visibility, protected write fields, transactional CRUD auditing and the last-admin invariant
- task Query Tags: aliases, icons, date/status boundaries, invalid-value fallback and backoffice authentication
- task status handlers: role access, POST-only requests, task ID validation, server-selected targets, transition matrix, idempotency, rollback on audit failure and concurrent writes
- browser smoke: backoffice login, CSRF rejection, direct task transition, terminal confirmation and async table refresh

## CI

GitHub Actions runs dependency audits, the current checks, the frontend build, the HTTP smoke test and the Playwright backoffice smoke on pushes to `main` and pull requests.
