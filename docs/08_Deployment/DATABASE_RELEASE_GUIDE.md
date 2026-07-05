# Database Release Guide

This guide covers the database side of a Hostinger release.

## Create Hostinger MySQL Database

1. Open Hostinger control panel.
2. Create a MySQL database.
3. Create a database user with a strong password.
4. Assign the user to the database with required privileges.
5. Copy the database host, port, name, username, and password into the real production `.env`.

Use:

```env
DB_CONNECTION=mysql
DB_HOST=hostinger_database_host
DB_PORT=3306
DB_DATABASE=hostinger_database_name
DB_USERNAME=hostinger_database_user
DB_PASSWORD=hostinger_database_password
```

## Migration Path With SSH

Use this when SSH and Artisan are available:

```bash
php artisan migrate --force
php artisan db:seed --force
```

Run seeders only when approved. Production seeders should contain required master data such as catalog masters, settings, delivery slots, payment settings defaults, and role/permission-ready records. Avoid demo users, test orders, fake payments, and local-only sample data unless approved for launch.

## phpMyAdmin Import Path Without SSH

Use this when File Manager and phpMyAdmin are the only available tools:

1. Prepare a clean local or staging database.
2. Export SQL from the known-good database.
3. Back up the current Hostinger database.
4. Import the SQL through Hostinger phpMyAdmin.
5. Verify table counts for users, products, variants, inventory, settings, orders, payments, coupons, cashback, and tax report source tables.
6. Confirm the app connects with the production `.env`.

## Backup Before Migration Or Import

Always capture:

- Database SQL export before changes.
- Release ZIP being deployed.
- Current `.env` values stored securely.
- Current migration status if SSH is available:

```bash
php artisan migrate:status
```

## Rollback Database Plan

Rollback database only when schema or data changes caused the failure.

1. Export the failed-state database for investigation.
2. Import the pre-release SQL backup.
3. Clear Laravel caches.
4. Verify homepage, admin login, products, cart, checkout, orders, inventory, payment settings, coupons, cashback, and GST reports.
5. Document the failed migration/import step.

## Production Data Rules

- Do not import local test orders into production.
- Do not import fake customer data into production.
- Do not import demo payments into production.
- Do not overwrite real orders, inventory, or payment records without a signed-off rollback decision.
- Keep required master seed data separate from demo/test data.
