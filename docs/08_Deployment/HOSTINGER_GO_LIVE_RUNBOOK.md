# Hostinger Go-Live Runbook

Use this runbook on launch day. Keep a calm, step-by-step record of what changed, when it changed, and who approved it.

## 1. Freeze And Backup

- Confirm no new business work is being merged.
- Confirm `git status` is clean locally.
- Confirm the final release ZIP name and timestamp.
- Download a backup of the existing Hostinger website files.
- Export the current production database from phpMyAdmin if one exists.
- Save the previous `.env` values securely without sharing secrets.

## 2. Prepare Hostinger

- Confirm PHP 8.4 is selected.
- Create or confirm the MySQL database and database user.
- Confirm the domain or subdomain points to the intended document root.
- Decide whether the Laravel project can live outside `public_html`.
- Confirm SSL is active before enabling secure cookies.

## 3. Upload Release

File Manager path:

1. Upload the release ZIP.
2. Extract it to the selected project folder.
3. Copy Laravel `public/` contents into `public_html` if the project is outside the web root.
4. Adjust `public_html/index.php` paths to the real project folder.
5. Create `.env` manually using `.env.production.example`.
6. Set `APP_ENV=production`, `APP_DEBUG=false`, and the correct `APP_URL`.

SSH path:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Run `php artisan db:seed --force` only for approved master data.

## 4. Database

- If SSH is available, run migrations with `php artisan migrate --force`.
- If SSH is unavailable, import the prepared SQL file through phpMyAdmin.
- Do not import local test users, demo orders, or test payments into the real production store unless approved.
- Confirm admin user access and master settings after import.

## 5. Storage

- Run `php artisan storage:link` when SSH is available.
- If symlinks are unavailable, use Hostinger File Manager symlink support or copy `storage/app/public` to `public/storage`.
- Verify product images, category images, brand images, QR image, and payment proof paths.

## 6. Cache

After `.env`, database, storage, and public paths are correct:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

If something points to the wrong URL or database, clear caches first:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

## 7. Final Launch Checks

- Homepage loads.
- Product listing and product detail load.
- Add to cart works.
- COD checkout creates an order.
- Admin login works.
- Admin can view orders.
- Storage images load.
- `APP_DEBUG=false`.
- Browser cannot download `.env`.

## 8. Rollback Trigger

Rollback if any of these fail and cannot be fixed quickly:

- Homepage unavailable.
- Admin login unavailable.
- Checkout unavailable.
- Orders not saved correctly.
- Inventory changes incorrectly.
- `.env` or private folders exposed.
- Database migration/import corrupts real data.

Rollback using `ROLLBACK_PLAN.md`, then document the failed step and root cause.
