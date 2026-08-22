# Multi-Store Operations

Milestone 4.23 uses existing `stock_locations` as stores. Product, brand, category, and variant master data remains global. Store-specific behavior is layered through stock location assignment, inventory rows, store price overrides, and store-scoped cart/order records.

## Store Identity

- `stock_locations` is the store table.
- The existing/default Main Store remains valid as the default store when `is_default = 1`.
- A customer may be assigned to one store through `customers.assigned_store_id`.
- An admin user may be assigned to one store through `users.assigned_store_id`.
- Carts, pending orders, orders, and daily offers can carry `stock_location_id`.
- If no explicit default store exists, legacy global cart/inventory behavior is preserved.

## Pricing

- Global variant prices remain on `product_variants`.
- Store-specific prices live in `store_variant_prices`.
- Effective customer cart snapshots use:
  1. active/effective `store_variant_prices` for the cart store,
  2. fallback to `product_variants.selling_price` and `product_variants.mrp`.
- Price changes write compact rows to `store_variant_price_histories`.
- Daily Fresh Price writes one authoritative store override per store/variant.
- Future effective prices are represented through `effective_from` / `effective_until`.
- Rapid-price grids are driven by `categories.rapid_price_update_enabled`, not runtime category-name checks.
- The migration initially enables likely Fruits/Vegetables categories by existing slug/name, then administrators manage the flag in Category admin.

## Stock And Checkout

- Inventory remains keyed by `product_variant_id + stock_location_id`.
- Assigned-store customer carts reserve only that store's inventory.
- Checkout locks and deducts inventory for the order/cart store when a store is present.
- Orders snapshot `store_name_snapshot`, `store_code_snapshot`, `brand_id_snapshot`, and `brand_name_snapshot`.
- Customer Serving Store reassignment releases active old-store reservations, moves active carts to the new store, revalidates items, refreshes normal store prices, and reopens reservations only for valid remaining items.
- Normal cart holds do not extend Daily Offer reservations.

## Roles

- `SUPER_ADMIN`: full access.
- `STORE_MANAGER`: inventory, orders, reports, customers, and daily offers for assigned store.
- `CART_FOLLOW_UP_EMPLOYEE`: pending order/customer follow-up access for assigned store.
- Existing configured admin emails continue to receive full access.
- New users do not become super admins unless `role = 'SUPER_ADMIN'` is set explicitly.
- Super Admins get an admin store-context selector with All Stores for read/report pages.
- Mutation pages must post one explicit authorized store when a store-scoped record is created.
- Store Managers and Cart Follow-up Employees are restricted server-side to their assigned store.

## Storefront Content

- Homepage sections and banners now support optional `stock_location_id`.
- `homepage_sections` uses a generated `homepage_section_store_identity = COALESCE(stock_location_id, 0)` to prevent duplicate global keys while allowing per-store overrides.
- Section icons use `homepage_sections.icon_path`.
- Customer announcements and marketing banners have store pivots and current-window scopes.
- Homepage content resolves store override first, then global/default.
- Page backgrounds support global/default and store override rows for homepage, category, search, cart, checkout, and customer account pages.
- Announcement audiences are compact: all customers, selected stores, or selected individual customers. Store/all audiences do not create per-customer rows.
- Customer account pages render one priority announcement strip and up to five applicable marketing banners near the bottom before the footer.

## Cleanup

- `prices:cleanup-history` removes compact store price history older than 90 days by default. It never touches order, invoice, accounting, tax, or payment history.
- `announcements:cleanup` hard-deletes inactive/ended announcements after 15 days by default, including pivots and dismissals. Future scheduled announcements are not purgeable.
- `marketing-banners:cleanup` hard-deletes inactive/ended marketing banners after 30 days by default.
- Each cleanup command supports `--dry-run`.
- Scheduler registration uses Laravel scheduler only, Asia/Kolkata, and `withoutOverlapping`.
- `storage/framework/scheduler-heartbeat.json` is runtime state and must stay ignored by Git.

## Deployment

- Do not modify already deployed migrations.
- Apply the two new additive migrations in order:
  1. `2026_08_16_000001_add_multi_store_operational_fields`
  2. `2026_08_16_000002_create_store_pricing_announcements_and_storefront_overrides`
- phpMyAdmin SQL is required only if production migrations are applied manually.
- Before applying SQL, confirm `stock_locations`, `users`, `customers`, `carts`, `pending_orders`, `orders`, `daily_offers`, `order_items`, `product_variants`, `categories`, `homepage_sections`, and `homepage_banners` exist.
- After applying SQL, assign one active default store:

```sql
UPDATE stock_locations
SET is_default = 1, status = 1, accepts_online_orders = 1
WHERE code = 'MAIN';
```

Backfills performed by `2026_08_16_000001` use the active default store (`is_default = 1`, not deleted) or the first non-deleted stock location:

- `customers.assigned_store_id`
- `carts.stock_location_id`
- `pending_orders.stock_location_id`
- `orders.stock_location_id`
- `purchase_entries.stock_location_id`
- `daily_offers.stock_location_id`

The migration does not delete historical rows.

## Preflight SQL

```sql
SELECT COUNT(*) AS stock_locations_count FROM stock_locations;
SELECT COUNT(*) AS users_count FROM users;
SELECT COUNT(*) AS customers_count FROM customers;
SELECT COUNT(*) AS carts_count FROM carts;
SELECT COUNT(*) AS pending_orders_count FROM pending_orders;
SELECT COUNT(*) AS orders_count FROM orders;
SELECT COUNT(*) AS daily_offers_count FROM daily_offers;
SELECT COUNT(*) AS homepage_sections_count FROM homepage_sections;
SELECT COUNT(*) AS homepage_banners_count FROM homepage_banners;

SELECT code, COUNT(*) AS duplicates
FROM stock_locations
GROUP BY code
HAVING COUNT(*) > 1;

SELECT id, name, code
FROM stock_locations
WHERE is_default = 1 AND deleted_at IS NULL;

SELECT table_name, engine
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN ('stock_locations','users','customers','carts','pending_orders','orders','daily_offers','order_items','product_variants','categories','homepage_sections','homepage_banners');
```

## Core SQL Summary

The migrations add store assignment columns, store price tables, homepage store override fields, page backgrounds, customer announcement tables, and customer marketing banner tables. The exact phpMyAdmin SQL must follow Laravel `migrate --pretend` output for the two 2026-08-16 migrations, with conditional default-store backfill updates executed only after a default/first stock location id is selected.

## Manual Smoke Checklist

1. Super Admin: switch All Stores and a specific store in the admin selector.
2. Store Manager: confirm no unrestricted selector and direct other-store URLs return 403.
3. Customer: assign Serving Store, add cart, reassign store, confirm old reservations are released.
4. Purchase: create/preview/import with explicit store and confirm stock movement store id.
5. Daily Fresh Price: enable rapid pricing on a category, update a store price, verify storefront/cart snapshot uses that store price.
6. Homepage: create global and store override sections/banners and verify customer fallback.
7. Announcements: create all/store/customer audience, dismiss as customer, run dry-run cleanup.
8. Marketing banners: create more than five applicable banners and confirm only five render.
9. Scheduler/System Health: confirm heartbeat and cleanup commands appear in `schedule:list`.
