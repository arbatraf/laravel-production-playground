# CI/CD

GitHub Actions runs the quality gate on pushes to `main` and pull requests.

## Current Workflow

- `.github/workflows/ci.yml`

## Checks

```bash
composer audit --locked --abandoned=fail
yarn audit --level high
composer run check
yarn build
yarn test:e2e
```

`composer run check` includes Composer validation, Pint, PHPStan/Larastan level 6 and PHPUnit.

Workflow actions are pinned to full commit SHAs. The job has a 15-minute timeout, and a newer run cancels an older run for the same branch.

The CI job uses a temporary MySQL service and `.env.example`; repository secrets are not required.

## Repository Settings

`main` accepts direct pushes. The quality gate runs after each push.
