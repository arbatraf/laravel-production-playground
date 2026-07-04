# Testing

The foundation uses PHPUnit and Laravel Pint.

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
```

`phpunit.xml` uses an in-memory SQLite database for tests. Local development uses MySQL through `.env.example`.

## Current Coverage

- public foundation page loads
- application name matches the project name
- Laravel health route is available
- API health/readiness endpoints report infrastructure status

## Planned Checks

PHPStan/Larastan level 6 and Playwright smoke tests are planned for slices that need them. They are not installed in the foundation slice.

Every future slice should add focused tests before moving to the next slice.
