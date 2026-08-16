# Release Candidate Checklist

Use this gate before creating the next combined release ZIP. Do not run production SQL from this document without an approved deployment window and backup.

## Code Gate

- Confirm `php artisan test`, targeted recent-module filters, `php artisan route:list`, `php vendor/bin/pint --test` or scoped Pint fallback, `npm run build`, and `git diff --check` pass.
- Confirm no tracked `.env`, local database files, debug dumps, screenshots, caches, or temporary QA scripts are included.
- Confirm secret-shaped tracked-file scan has no real production credentials.
- Confirm no deployed migration file is modified in the release diff.

## DB Gate

- Verify recent production migration rows are registered exactly once:

```sql
SELECT migration, batch
FROM migrations
WHERE migration IN (
  '2026_08_15_000001_create_customer_credit_transactions_table',
  '2026_08_15_000002_add_customer_credit_redemption_and_coupon_purpose_fields',
  '2026_08_15_000003_create_razorpay_webhook_events_table',
  '2026_08_15_000004_add_low_stock_state_to_inventories_table',
  '2026_08_15_000005_create_homepage_content_management_tables',
  '2026_08_15_000006_add_customer_address_approval_metadata'
)
ORDER BY migration;
```

- Verify recent schema exists:

```sql
SELECT table_name
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN (
    'customer_credit_transactions',
    'coupon_customer',
    'razorpay_webhook_events',
    'homepage_sections',
    'homepage_section_categories',
    'homepage_section_products',
    'homepage_banners',
    'associated_partners'
  )
ORDER BY table_name;

SELECT table_name, column_name
FROM information_schema.columns
WHERE table_schema = DATABASE()
  AND (
    (table_name = 'inventories' AND column_name IN ('low_stock_state', 'low_stock_notified_at'))
    OR (table_name = 'customer_addresses' AND column_name IN ('approval_status', 'rejection_reason', 'approval_status_changed_at'))
    OR (table_name = 'orders' AND column_name IN ('original_delivery_charge', 'delivery_discount_total', 'amount_before_customer_credit', 'customer_credit_used', 'coupon_purpose_snapshot'))
    OR (table_name = 'coupons' AND column_name IN ('purpose', 'audience'))
    OR (table_name = 'customer_credit_transactions' AND column_name = 'idempotency_key')
  )
ORDER BY table_name, column_name;
```

Expected result: six migration rows, eight listed tables, and all listed columns.

## Backup Gate

- Export the production database.
- Archive `public_html/uploads`.
- Securely back up the live `.env`.
- Keep the current production code backup or previous release ZIP.
- Record the current Git commit and current production release label.

## Deployment Gate

1. Confirm backups.
2. Confirm required SQL/migration registration is already complete or approved.
3. Create the release package.
4. Upload package to a temporary server folder.
5. Extract and replace app code only.
6. Preserve `.env`, `storage`, and `public_html/uploads`.
7. Copy public assets to the intended public folder.
8. Verify `public/index.php` path assumptions.
9. Clear config/cache/view/route caches.
10. Run the smoke test below.

## Smoke Test

Run for 20-30 minutes using existing test data:

1. Homepage and key CMS sections.
2. Search, filters, sorting, pagination.
3. Customer login.
4. Account dashboard, addresses, orders, notifications.
5. Normal cart add/update/remove.
6. COD checkout.
7. Admin order view and status movement.
8. Inventory quantity effect.
9. Coupon apply/replace/remove.
10. Customer Credit partial/full usage.
11. Daily Offer cart/checkout.
12. Purchase entry and replenishment.
13. Admin/customer notifications.
14. System Health.
15. Scheduler heartbeat.
16. Print previews.
17. Razorpay Test Mode separately.

## Razorpay Manual Test

MANUAL REQUIRED with test credentials:

1. Configure Test mode with test key id, secret, and webhook secret.
2. Create a normal successful payment.
3. Simulate failed payment.
4. Cancel popup.
5. Retry payment.
6. Refresh after payment.
7. Use delivery coupon.
8. Use partial Customer Credit.
9. Use full Customer Credit and confirm provider bypass.
10. Deliver webhook.
11. Observe callback/webhook race.
12. Confirm inventory, coupon usage, and credit ledger apply exactly once.

## Scheduler Manual Test

MANUAL REQUIRED after deploy:

- Visit Admin System Health.
- Observe scheduler heartbeat.
- Wait 2-3 minutes.
- Refresh and confirm heartbeat advances.
- Confirm pending-order, cart cleanup, monthly risk, low-stock, and heartbeat commands remain scheduled.

## Print Manual Test

MANUAL REQUIRED:

- Invoice A4 preview.
- Packing slip A4 preview.
- Picking slip A4 preview.
- Purchase print A4 preview.
- Confirm no clipped columns, no admin chrome/sidebar, visible totals, and acceptable page breaks.

## Rollback Trigger

Rollback immediately for P0/P1 issues:

- Secret or customer data exposure.
- Payment corruption or duplicate finalization.
- Inventory corruption or negative stock.
- Tax/financial total corruption.
- Checkout unavailable.
- Admin cannot process orders.
- Broken DB dependency.
- Critical production JavaScript error blocking purchase/account workflows.
