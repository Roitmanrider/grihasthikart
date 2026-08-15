# GrihasthiKart Production Operations

This runbook is for Hostinger shared hosting with PHP 8.4, MySQL 8, database sessions, Laravel scheduler cron every minute, and public uploads stored outside the Laravel app root through `UPLOADS_PUBLIC_PATH=../public_html/uploads`.

Do not store production secrets in Git, release ZIP files, tickets, screenshots, or chat logs.

## Security Baseline

- `APP_ENV=production`
- `APP_DEBUG=false`
- `SESSION_DRIVER=database`
- `SESSION_SECURE_COOKIE=true`
- `SESSION_HTTP_ONLY=true`
- `SESSION_SAME_SITE=lax`
- `LOG_CHANNEL=daily`
- `LOG_LEVEL=warning`
- `LOG_DAILY_DAYS=14`

Admin and customer logins regenerate the Laravel session ID after successful authentication. Customer session records are stored separately and can revoke other sessions without exposing token hashes.

State-changing browser routes use POST, PATCH, PUT, or DELETE with CSRF protection. Razorpay webhooks are exempt from CSRF because signature verification on the raw request body is authoritative.

HTTP responses include a safe baseline of security headers: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, and `Permissions-Policy`. HSTS is applied only in production over HTTPS.

Uploads are limited to validated files and stored with generated filenames. SVG and executable/script extensions are rejected. The storage helper rejects traversal paths and only deletes normalized paths beneath `uploads/`.

## Rate Limits

- Admin login: 5 attempts per minute per email/IP.
- Customer OTP request: 5 attempts per minute per mobile/IP.
- Customer OTP verify: 8 attempts per minute per mobile/IP.
- Catalog autocomplete: 60 requests per minute per session/IP.
- Contact form: 3 submissions per minute per IP.
- Coupon apply: 10 attempts per minute per session/IP.
- Razorpay retry: 6 attempts per minute per session/IP.
- Customer-sensitive actions such as cashback redemption: 12 per minute per session/IP.

Rate limits are temporary abuse controls. They do not permanently lock an account.

## Scheduler

Cron should run every minute:

```text
php artisan schedule:run
```

Registered scheduled commands:

- `ops:scheduler-heartbeat` every minute, without overlapping. Records scheduler freshness for Admin System Health.
- `pending-orders:process` every five minutes, without overlapping. Processes pending cart/order lifecycle.
- `inventory:check-low-stock` every fifteen minutes, without overlapping. Updates replenishment planning signals.
- `cart-activity:cleanup` daily at 02:10, without overlapping. Prunes eligible cart activity details according to retention policy.
- `cart-activity:generate-monthly-risk` monthly on day 1 at 02:30, without overlapping. Generates monthly cart risk summaries idempotently.

Business date boundaries should use `APP_TIMEZONE=Asia/Kolkata`.

## Admin System Health

Admin System Health is available only to authorized admins at:

```text
/admin/system-health
```

It reports environment, debug status, PHP version, database connectivity, required tables, storage/cache/uploads writability, scheduler heartbeat freshness, Razorpay configured/mode/webhook-secret status without printing secrets, queue driver, and session driver.

It never shows DB passwords, API secrets, webhook secrets, APP_KEY, session IDs, or raw provider signatures.

## Data Retention

Permanent:

- orders and order items
- payments and payment histories
- inventory movements and purchase records
- customer credit transactions
- return records and refund/credit references
- coupon usage

Medium-term operational:

- Razorpay webhook events: retain for months, not days, so provider retries remain idempotent and auditable.
- customer sessions: prune only expired/revoked rows after operational review; active sessions must remain.
- notifications: customer/admin operational history; do not aggressively delete financial or order-related notification context.
- cart activity details: keep existing 4.21F-C policy: converted summaries short retention, abandoned/expired detail through monthly risk plus grace, monthly risk six completed months.

Disposable:

- Laravel logs beyond configured daily retention.
- compiled Blade views and bootstrap cache files after deployment.
- stale framework cache entries.

## Backup Procedure

Use Asia/Kolkata naming:

- `grihasthikart-db-YYYYMMDD-HHMM.sql.gz`
- `grihasthikart-uploads-YYYYMMDD-HHMM.zip`
- `grihasthikart-env-YYYYMMDD-HHMM.txt` stored securely outside Git/release ZIP.

Before every release with SQL/schema changes:

1. Back up the production MySQL database from phpMyAdmin.
2. Back up `public_html/uploads/` if upload-related features changed or before a major release.
3. Securely copy `.env`; never commit it.
4. Verify backup files exist and have nonzero size.
5. Record current Git commit/release name.
6. Apply production SQL manually.
7. Deploy code.
8. Smoke test before reopening traffic.

phpMyAdmin database export:

1. Select the exact production database.
2. Open Export.
3. Choose Custom.
4. Format SQL.
5. Include structure and data.
6. Include `DROP TABLE` only for a full restore package.
7. Include triggers/routines only if the project starts using them.
8. Use gzip compression if available.
9. Download and verify the file opens and contains the `migrations` table.

Uploads backup:

1. In Hostinger File Manager, navigate to `public_html/uploads/`.
2. Compress/download the whole uploads folder.
3. Confirm expected folders exist, including categories, brands, products, site, banners, partners, payments, and temp if present.
4. Store outside the hosting account where practical.

Storage backup:

Database sessions mean session state is in MySQL. Normally back up durable uploads and database, not framework caches. Include private `storage/app` files only if future features store durable private documents there.

## Restore Procedure

Database restore:

1. Prefer restoring into a temporary/test database first.
2. Confirm MySQL version and charset compatibility.
3. Import the SQL through phpMyAdmin.
4. Verify `migrations` table exists.
5. Verify critical counts: customers, products, variants, inventories, orders, payments.
6. Point a test copy of the app to the restored DB if possible.
7. Restore production only after confirming the backup is the intended point in time.

Uploads restore:

1. Extract the uploads archive into a temporary folder.
2. Compare against current `public_html/uploads/`.
3. Replace only intentionally; do not blindly overwrite newer customer/admin uploads.
4. Preserve folder permissions.
5. Smoke test product, category, banner, QR, and payment proof images.

Never roll back a database by deleting migration rows alone.

## Deployment Checklist

1. Create DB backup.
2. Create uploads backup when relevant.
3. Securely preserve `.env`.
4. Confirm release commit hash and migration names.
5. If migration is required, apply phpMyAdmin SQL before code when old code remains compatible or as noted in release notes.
6. Upload release ZIP to a temporary folder.
7. Extract.
8. Replace app code without overwriting `.env`, `storage/`, or `public_html/uploads/`.
9. Copy public assets into `public_html` as required by current Hostinger layout.
10. Verify `public_html/index.php` paths.
11. Clear generated caches manually if no SSH:
    - `bootstrap/cache/config.php`
    - `bootstrap/cache/routes.php`
    - `bootstrap/cache/routes-v7.php`
    - `bootstrap/cache/services.php`
    - `bootstrap/cache/packages.php`
    - `bootstrap/cache/events.php`
    - files inside `storage/framework/views/`
12. Do not delete the `bootstrap/cache` or `storage/framework/views` directories themselves.
13. Smoke test storefront, admin login, cart, checkout, product images, scheduler heartbeat, and System Health.

## Rollback

For code-only failure:

1. Put site into maintenance mode if available.
2. Restore previous code release.
3. Preserve `.env`, `storage/`, and `uploads`.
4. Clear caches.
5. Smoke test.

For DB-related failure:

1. Identify whether the new migration is additive and previous code can ignore it.
2. Prefer rolling back code only when schema is additive.
3. Restore DB from backup only when data/schema incompatibility is confirmed.
4. Restore uploads only if uploads were affected.

Additive migrations are operationally safer because old code usually ignores new columns/tables.

## Performance Notes

High-growth tables: orders, order_items, payments, notifications, carts, cart_items, pending_orders, customer sessions, customer credit transactions, coupon usages, inventories, purchases, stock movements, Razorpay webhook events.

Current pages use pagination for high-growth admin/customer lists. Dashboard/account views use aggregates and limited recent rows. Catalog autocomplete is throttled. MySQL `LIKE` search is acceptable for the current grocery catalog size; if products/variants grow into very large ranges or autocomplete latency becomes visible, evaluate FULLTEXT or a dedicated search engine in a later infrastructure milestone.

Do not introduce Redis, queues, Supervisor, Docker, or VPS-only dependencies for shared hosting.

## Manual Security Checklist

1. Guest opens `/admin` and is redirected to admin login.
2. Customer tries `/admin/system-health` and is denied.
3. Customer changes another customer's address/order/return/notification ID and gets 404/denied.
4. Repeat failed admin/customer login and confirm throttle.
5. Hit autocomplete rapidly and confirm throttle.
6. Send Razorpay webhook with invalid signature and confirm rejection.
7. Upload valid JPG/PNG/WebP image.
8. Upload fake PHP/script file and confirm rejection.
9. Confirm production `APP_DEBUG=false`.
10. Use logout other sessions and confirm current session remains.
11. Confirm health page shows configured/missing, not secrets.
12. Confirm payment amount cannot be forged from the browser.

## Manual Performance Checklist

1. Homepage load.
2. Product search.
3. Autocomplete.
4. Category page.
5. Customer account overview.
6. Admin orders page.
7. Replenishment page.
8. Notifications page.
9. Query log spot-check for obvious N+1.
10. Pagination on large lists.

## Manual Backup Checklist

1. Export database.
2. Verify export size and open the file.
3. Confirm SQL contains `migrations`.
4. Zip/download `public_html/uploads/`.
5. Securely copy `.env`.
6. Record Git commit/release name.
7. Store backup outside hosting account where possible.
8. Keep previous known-good backup.
