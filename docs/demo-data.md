# Demo Data

Seed demo data:

```bash
./scripts/php artisan migrate --seed
```

Demo seeding is limited to local and testing environments.

Reset the local demo database:

```bash
./scripts/php artisan migrate:fresh --seed
```

Demo accounts:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@example.com` | `password` |
| Manager | `manager@example.com` | `password` |
| Viewer | `viewer@example.com` | `password` |

The seeder uses fake companies, contacts, tasks and notes. Repeated runs restore soft-deleted demo records without changing seeded values.

Do not add real customer data, personal emails, phone numbers, tokens or provider payloads to seeders.
