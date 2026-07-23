# DeviceScan Documentation

Start with `../../AGENTS.md`, then read:

1. `OVERVIEW.md`
2. `ARCHITECTURE.md`
3. `ENGINEERING_DECISIONS.md`
4. `EXISTING_PIPELINE.md`
5. `compiler/COMPILER.md`

Then follow the module or inverter-specific docs as needed.

## Local PostgreSQL 17 Recovery

LineWatt Library local development currently targets PostgreSQL 17 for the main application database.

Recommended local `.env` database settings:

```text
APP_ENV=local
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=devicescan
```

Create the local database if it does not exist:

```bash
createdb -h 127.0.0.1 -p 5432 devicescan
```

Before resetting data, verify the target is local:

```bash
php artisan env
php artisan migrate:status
```

For a confirmed local development database, restore schema and seed reference/demo data:

```bash
php artisan migrate:fresh --seed --force
php artisan optimize:clear
```

The main seeder always restores reference data such as Power Search taxonomy and platform services. Demo/test accounts are seeded only in `local` or `testing`.

Demo passwords are controlled by:

```text
DEMO_USER_PASSWORD=
```

If unset in local/testing, the existing local demo fallback is used. Do not use demo credentials in production, staging or shared environments.

Seeded local demo accounts include:

- `super@linewatt.test` — Super Admin
- `admin@linewatt.test` — Admin
- `librarian@linewatt.test` — Librarian
- `library-publisher@linewatt.test` — Library Publisher
- `library-champion@linewatt.test` — Library Champion
- `library-champion-2@linewatt.test` — Library Champion
- `registered@linewatt.test` — Registered user without an active plan
- `subscriber@linewatt.test` — Subscriber
- `trina-admin@linewatt.test` — Manufacturer Admin, Trina Solar
- `trina-user@linewatt.test` — Manufacturer User, Trina Solar
- `vikram-admin@linewatt.test` — Manufacturer Admin, Vikram Solar
- `vikram-user1@linewatt.test` — Manufacturer User, Vikram Solar
- `vikram-user2@linewatt.test` — Manufacturer User, Vikram Solar
- `canadian-admin@linewatt.test` — Manufacturer Admin, Canadian Solar
- `canadian-user1@linewatt.test` — Manufacturer User, Canadian Solar
- `canadian-user2@linewatt.test` — Manufacturer User, Canadian Solar
- `canadian-user3@linewatt.test` — Manufacturer User, Canadian Solar

After recovery, run focused authorization checks:

```bash
php artisan test tests/Feature/LineWattRoleResponsibilityTest.php tests/Feature/LineWattAdminOnboardingTest.php tests/Feature/LineWattPlatformAdminConsoleTest.php
```
