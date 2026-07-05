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
composer run test
composer run pint:test
composer run analyse
```

`phpunit.xml` uses an in-memory SQLite database for tests. Local development uses MySQL through `.env.example`.

## Current Coverage

- public foundation page loads
- application name matches the project name
- Laravel health route is available
- API health/readiness endpoints report infrastructure status

## CI

GitHub Actions runs the current checks on pushes to `main` and pull requests.

## Planned Checks

Playwright smoke tests are planned for slices that need browser coverage.

Every future slice should add focused tests before moving to the next slice.
