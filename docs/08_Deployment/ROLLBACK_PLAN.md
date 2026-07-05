# Rollback Plan

Rollback is required when deployment breaks the public storefront, admin access, checkout, payment settings, inventory updates, or database integrity.

## Before Deployment

- Download current Hostinger files as a backup ZIP.
- Export the current production database from phpMyAdmin.
- Record the current release folder name and public document root mapping.
- Record the current `.env` values without sharing secrets.

## File Rollback

1. Put the site in maintenance mode if possible.
2. Rename the failed release folder for investigation.
3. Restore the previous release folder or upload the previous backup ZIP.
4. Restore the previous `public_html/index.php` path mapping.
5. Confirm `public/build` and `public/storage` exist.
6. Clear caches if SSH is available:

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

## Database Rollback

Use database rollback only when migrations or imports changed production data.

1. Export the failed-state database first for audit/debugging.
2. Import the pre-deployment SQL backup through phpMyAdmin.
3. Confirm order, inventory, payment, coupon, cashback, and tax report data.
4. Rebuild caches after the database is restored.

## Verification After Rollback

- Homepage loads.
- Admin login works.
- Product listing and detail pages load.
- Cart and checkout are not broken.
- Existing orders are visible.
- Storage images load.
- `APP_DEBUG=false`.
