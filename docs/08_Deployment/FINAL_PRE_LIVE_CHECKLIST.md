# Final Pre-Live Checklist

Complete this checklist before replacing the old Hostinger site.

## Code And Package

- Working tree is clean.
- Release ZIP is created from the final commit.
- Full test suite passed for real go-live, or approved selected test filters passed for documentation-only release.
- `php vendor/bin/pint --test` passed.
- `npm run build` passed.
- `public/build` exists in the release package.
- No business logic changes are included in the deployment-only release.

## Environment

- `.env` will be created manually on Hostinger.
- `.env.production.example` contains placeholders only.
- `APP_ENV=production`.
- `APP_DEBUG=false`.
- `APP_URL` is the live domain or subdomain.
- `APP_KEY` is set.
- `GRIHASTHIKART_ADMIN_EMAILS` contains only approved admin emails.
- Razorpay live is disabled unless live integration is separately approved and verified.

## Database

- Hostinger MySQL database exists.
- Database user and password are ready.
- Database backup exists before migration/import.
- Migration or phpMyAdmin import strategy is chosen.
- Seed strategy is approved: master data only, no demo/test data unless explicitly approved.

## Storage And Public Files

- Hostinger folder structure is chosen.
- Laravel private files are outside the public web root whenever possible.
- `public_html/index.php` path changes are ready if needed.
- `storage/app/public` media is included or synced.
- `public/storage` symlink or fallback copy strategy is ready.
- `storage` and `bootstrap/cache` permissions are writable by PHP.

## Business Smoke Test Plan

- Homepage.
- Product listing.
- Product detail.
- Cart add/update/remove.
- COD checkout.
- QR payment settings.
- Admin login.
- Admin order view.
- Customer login/account.
- Coupon apply.
- Cashback page.
- GST report.

## Backup

- Current website files are downloaded.
- Current database is exported.
- Current public root mapping is recorded.
- Rollback owner and rollback decision point are known.
