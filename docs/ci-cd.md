# CI/CD

GitHub Actions runs the foundation quality gate on pushes to `main` and pull requests.

## Current Workflow

- `.github/workflows/ci.yml`

## Checks

```bash
composer run check
yarn build
```

`composer run check` includes Composer validation, Pint, PHPStan/Larastan level 6 and PHPUnit.

The CI job uses a throwaway MySQL service and values from `.env.example`. No repository secrets are required for the foundation gate.
