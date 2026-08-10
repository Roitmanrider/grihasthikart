# Pending Orders Deployment Notes

Milestone 4.21F-B uses Laravel Scheduler for unattended pending-cart reminders and expiry processing.

The application command is:

```bash
php artisan pending-orders:process
```

The Laravel Scheduler registration runs it every five minutes with `withoutOverlapping()`.

Production hosting should invoke Laravel's scheduler runner on a regular interval:

```bash
php artisan schedule:run
```

This is hosting-provider agnostic. If hosting changes, configure the new infrastructure to invoke Laravel Scheduler; no pending-order business logic should change.

Before applying `2026_08_10_000002_create_pending_orders_and_cart_revision`, run the read-only preflight SQL from the release report. If duplicate live active carts are found, stop and review the data manually before migration.
