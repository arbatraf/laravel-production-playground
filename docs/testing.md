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
```

`phpunit.xml` uses an in-memory SQLite database for tests. Local development uses MySQL through `.env.example`.

Composer scripts use `./scripts/php`, so Laravel tools run on PHP 8.5 even when global CLI PHP differs.

## Current Coverage

- public foundation page loads
- application name matches the project name
- Laravel health route is available
- API health/readiness endpoints report infrastructure status
- architecture guard suite covers Yarn commands, MoonShine 4 namespaces and debug helpers in production paths

## CI

GitHub Actions runs the current checks on pushes to `main` and pull requests.

## Planned Checks

Playwright smoke tests are planned for slices that need browser coverage.

Every future slice should add focused tests before moving to the next slice.
