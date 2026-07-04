# Testing

The foundation uses PHPUnit and Laravel Pint.

## Current Checks

```bash
composer validate --strict
php artisan test
vendor/bin/pint --test
yarn build
```

`phpunit.xml` uses an in-memory SQLite database for tests. Local development uses MySQL through `.env.example`.

## Current Coverage

- public foundation page loads
- application name matches the project name

## Planned Checks

PHPStan/Larastan level 6 and Playwright smoke tests are planned for slices that need them. They are not installed in the foundation slice.

Every future slice should add focused tests before moving to the next slice.
