# Milestone 4.22A Stabilization QA Matrix

Scope: cross-module stabilization through Milestone 4.21O. Automated rows reflect local PHPUnit coverage run during this milestone. Manual/browser rows are intentionally marked NOT TESTED until verified in a real browser at the listed viewport sizes.

| Module | Scenario | Desktop/Mobile | Expected Result | Status | Severity if Failed | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Routing | Laravel route registration | Server | Route list renders without missing controller/action registration errors. | PASS | P1 | `php artisan route:list` completed successfully. |
| Homepage | Homepage content, fallback sections, banners, partners | Automated | Homepage renders configured and fallback content without broken test-covered flows. | PASS | P1 | Covered by `Homepage` filter. |
| Homepage | Banner slider controls and visual overflow | Desktop 1366 / Mobile 360, 390 | Slider controls work, images fit, no horizontal overflow. | NOT TESTED | P2 | Requires real-browser interaction. |
| Search/Catalog | Search, filters, sort, noindex, autocomplete | Automated | Results are scoped, filtered, sorted, and autocomplete excludes private content. | PASS | P1 | Covered by `Search` and `Catalog` filters. |
| Search/Catalog | Pagination query state and Bootstrap controls | Automated | Pagination keeps query params and renders Bootstrap pagination, not oversized Tailwind SVG arrows. | PASS | P2 | Regression added in this milestone. |
| Search/Catalog | Mobile filter UI and Quick View console behavior | Mobile 360, 390 | Filter controls remain usable; Quick View opens without JS console errors. | NOT TESTED | P2 | Requires browser/device QA. |
| Product/Variants | CRUD, image assignment, visibility, delete safety | Automated | Product and variant workflows remain protected and valid. | PASS | P1 | Covered by `Product` filter. |
| Product Cards | Card metadata, prices, discounts, Daily Offer display | Desktop/Mobile | Variant selector and badges stay aligned; no sibling variants hidden. | NOT TESTED | P2 | Automated catalog coverage passed; visual QA still required. |
| Cart | Add/update/remove, shared cart, reservations, totals | Automated | Normal snapshots, Daily Offer holds, and shared cart revision rules pass. | PASS | P1 | Covered by `Cart` filter. |
| Daily Offer | Allocation, countdown, checkout, inventory deduction | Automated | Offer identity, reserve/sold math, and checkout source persistence pass. | PASS | P1 | Covered by `DailyOffer` filter. |
| Checkout | COD, QR/Razorpay paths, coupons, credit, delivery slots | Automated | Displayed and persisted totals remain consistent across covered combinations. | PASS | P0 | Covered by `Checkout`, `Payment`, `Razorpay`, `Coupon`, `CustomerCredit`, `DeliveryCharge`. |
| Checkout | Error placement in real browser | Desktop/Mobile | Global errors appear above sections; field errors remain near fields. | NOT TESTED | P2 | Requires browser QA with invalid form submissions. |
| Delivery Charges | Standard, premium, customer-specific, discounts | Automated | Merchandise amount, delivery discount, final delivery, and final amount remain persisted correctly. | PASS | P1 | Covered by `DeliveryCharge`, `Order`, `Checkout`. |
| Coupons | Merchandise, free delivery, delivery fixed/percent, assignment | Automated | One-coupon behavior, expiry, customer assignment, and historic snapshots pass. | PASS | P1 | Covered by `Coupon`. |
| Customer Credit | Refund, redemption, full/partial use, idempotency | Automated | Ledger and checkout redemption stay non-negative and persisted. | PASS | P0 | Covered by `CustomerCredit`, `Checkout`, `Order`. |
| Cashback | Eligibility, redemption multiple, coupon generation | Automated | Cashback separation from Customer Credit and coupon generation pass. | PASS | P1 | Covered by `Cashback`. |
| Orders | Admin/customer order workflows and documents | Automated | Status transitions, historical totals, and order documents pass. | PASS | P1 | Covered by `Order`. |
| Returns | Partial returns, duplicate protection, refund credit | Automated | Return lifecycle and customer/admin visibility pass. | PASS | P1 | Covered by `Return`. |
| Customer Account | Profile, addresses, orders, notifications, security | Automated | Ownership, address approval, notifications, and security flows pass. | PASS | P1 | Covered by `Customer`, `Address`, `Notification`, `Security`. |
| Customer Account | Responsive account navigation | Mobile 360, 390 / Tablet 768 | Navigation remains usable and active state is clear. | NOT TESTED | P2 | Requires browser QA. |
| Admin Dashboard | Cards, counts, links, responsiveness | Automated | Admin routes and covered management workflows pass. | PASS | P1 | Covered by `Admin`. |
| Admin Orders | List/detail financial fields and actions | Automated | Admin order operations and transition protections pass. | PASS | P1 | Covered by `Admin`, `Order`. |
| Admin Tables | Product, order, purchase, pending order, customer tables | Mobile 360, 390 / Tablet 768 | Tables remain readable or horizontally scrollable; action buttons stay reachable. | NOT TESTED | P2 | Static review found widespread table usage; real viewport QA required. |
| Product Import | CSV template, validation, history, export | Automated | Existing import/export tests pass through Product filter coverage. | PASS | P1 | Covered by `Product` filter. |
| Inventory | Inventory, movements, adjustment, verification, low stock | Automated | No negative-stock regressions in covered flows. | PASS | P0 | Covered by `Inventory`. |
| Replenishment | Low-stock suggestions and purchase prefill | Automated | Replenishment tests pass. | PASS | P1 | Included in `Inventory` and `Purchase` filters. |
| Purchases | List/detail/print, freight semantics, stock posting | Automated | Purchase workflows pass without redefining freight accounting. | PASS | P1 | Covered by `Purchase`. |
| Suppliers | Supplier CRUD and purchase history | Automated | Supplier management and purchase linkage pass. | PASS | P1 | Covered by `Supplier`. |
| Notifications | Admin and customer notification centers | Automated | Unread count, mark read, and ownership checks pass. | PASS | P1 | Covered by `Notification`. |
| Cart Activity | Pending cart activity, follow-up, risk, reminders | Automated | Pending/cart activity covered by Cart/Admin targeted filters. | PASS | P1 | Also covered in full suite. |
| Scheduler | Command registration and heartbeat | Static/Automated | Commands are registered without route/console errors. | PASS | P2 | No duplicate registration found in automated bootstrap path. |
| System Health | Admin-only health checks with no secrets | Automated | Health route authorization and status display remain covered. | PASS | P1 | Covered by `Security` and `Admin` filters. |
| Error Pages | Branded 404/403/419/500 | Automated | Error pages remain branded and avoid internals. | PASS | P2 | Covered by existing branded error tests in broader filters. |
| JS/CSS | Autocomplete, slider, cart polling, Razorpay disabled state | Desktop/Mobile | No console errors, null DOM exceptions, or broken interactions. | NOT TESTED | P2 | Requires browser console QA. |
| DB Consistency | Orphans, duplicate active carts, negative inventory | Local tests | Business flows avoid creating inconsistent records. | PASS | P0 | Non-destructive production SQL was not run. |
| Performance | Homepage/search/order list obvious N+1 regressions | Static/Automated | No test-covered performance regressions detected. | PASS | P2 | Deep query profiling NOT TESTED. |

## Manual QA Still Required

- Real browser responsive pass at 360px, 390px, 768px, and desktop.
- JS console pass on homepage slider, autocomplete, Quick View, cart polling, checkout, Razorpay test-mode/cancel flows.
- Print layout pass for invoice, packing slip, picking slip, and purchase print views.
- Real admin table readability pass for long product names, order IDs, coupon codes, payment references, and pending-order details.
- Production-like scheduler observation after deployment; no deployment was performed in this milestone.

## Milestone 4.22B Real-Browser QA Update

Browser: Chromium via Codex in-app browser against a local-only QA database (`grihasthikart_browser_qa`). No production SQL was run and no deployment was performed.

| Area | Pages / Flow | Viewports | Console / Network | Layout Result | Status | Notes |
| --- | --- | --- | --- | --- | --- | --- |
| Storefront | Home, Products, Categories, Daily Offers, Cart guest redirect, Customer Login, Admin Login | 360, 390, 430, 768, 1024, 1366 | No console errors after QA schema setup; no broken images found. | No content offenders after responsive fixes; intentional horizontal rails remain contained. | PASS | Initial local error was stale dev DB/config, corrected by isolated QA schema. |
| Pagination | Catalog/admin/customer pages inspected during browser pass | 360, 390, 768, 1024, 1366 | No JS errors. | Bootstrap pagination present; Tailwind `w-5 h-5` SVG classes not observed. | PASS | Complements 4.22A automated regression. |
| Admin | Dashboard, Products, Orders, Customers, Inventory, Replenishment, Purchases, Suppliers, Coupons, Daily Offers, Notifications, Homepage Sections, Banners, Partners, System Health | 360, 1024, 1366 spot pass, plus module navigation | No console errors on inspected pages. | Fixed page-level overflow from admin shell; dense tables scroll inside `.table-responsive`. | PASS | Replenishment route verified at `/admin/inventory/replenishment`. |
| Customer Account | Account, Profile, Addresses, Orders, Returns, Credit, Cashback, Coupons, Wishlist, Notifications, Security, Cart, Checkout empty-cart redirect | 360, 390, 768, 1366 | No console errors on inspected pages. | Fixed heading/badge wrap and shared footer tablet overflow. | PASS | Checkout with populated payment flow not exercised in browser. |
| Print | Invoice, packing slip, picking slip, purchase print | Browser print media | Not exercised. | Not exercised. | NOT TESTED | Requires seeded order/purchase print fixtures or production-like UAT data. |
| Razorpay | Test mode success/cancel/provider callback | Browser/provider flow | Not exercised. | Not exercised. | NOT TESTED | Requires Razorpay test credentials and a payable cart flow. |
| Scheduler | Pending reminders, lazy expiry, scheduled commands | Runtime observation | Not exercised. | Not applicable. | NOT TESTED | Requires scheduled command/runtime observation after deployment. |

## Milestone 4.22B Manual Checklist

- A. Storefront: verify home slider, category rails, product cards, Quick View, search/autocomplete, Daily Offer metadata, and footer at 360, 390, 430, 768, 1024, and desktop.
- B. Cart/Checkout: verify add/update/remove, Daily Offer reservation expiry messaging, delivery slot/address validation, COD, Razorpay test success/cancel, coupon, credit, and final totals.
- C. Customer Account: verify dashboard, profile, approved addresses, order history, returns, credit, cashback, coupons, wishlist, notifications, and security on mobile/tablet/desktop.
- D. Admin: verify all high-volume list pages with long names/references, table horizontal scroll, action buttons, filters, exports, imports, and flash/errors.
- E. Print: verify invoice, packing slip, picking slip, purchase print, page breaks, totals, and browser print dialog rendering.
- F. Payments: verify Razorpay disabled states, retry/cancel behavior, webhook/payment reconciliation, and COD fallback.
- G. Scheduler/Operations: verify pending order expiry/reminders, WhatsApp abstraction behavior, cleanup commands, system health, backup/restore pages, and logs.

## Milestone 4.22C Release Candidate Gate

Status: READY FOR RELEASE CANDIDATE, subject to the manual production-like gates below. No P0/P1 automated or static blocker was found during the RC audit.

| Gate | Result | Evidence | Manual Requirement |
| --- | --- | --- | --- |
| Repository hygiene | PASS | `.env` remains untracked; no migration diff; no debug stopper matches; no tracked secret-shaped matches from redacted scan. | Recheck before packaging. |
| Routes | PASS | `php artisan route:list --json` parsed 291 routes, no duplicate route names, no unexpected public admin endpoints except login. | Smoke admin/customer navigation after deploy. |
| Migrations | PASS | Recent six migration files are present and unchanged in current diff. | Verify production registration rows in phpMyAdmin before code deployment. |
| Schema assumptions | PASS | Recent table/column references map to existing migrations; homepage service keeps runtime `Schema::hasTable` fallbacks. | Run read-only schema verification SQL before release. |
| Checkout reconciliation | PASS | Existing tests cover COD, delivery tiers, customer overrides, coupon purposes, Customer Credit, Razorpay residual, Daily Offer, and free-delivery threshold boundary. | Run smoke checkout scenarios against production-like data. |
| Payment/Razorpay static | PASS | Server amount, exact paise conversion, signature verification, webhook idempotency, retry ownership, and Customer Credit bypass are covered by code/tests. | Razorpay Test Mode provider E2E is MANUAL REQUIRED. |
| Customer Credit/Coupons/Cashback | PASS | Idempotent credit/debit/restore, coupon replacement/assignment/purpose, and cashback separation are covered by targeted tests. | Smoke partial/full credit and coupon usage. |
| Inventory/Daily Offer | PASS | Locked inventory deduction, no negative-stock test paths, Daily Offer sale source/hold/allocation tests remain covered. | Smoke Daily Offer checkout and stock impact. |
| Orders/Returns/Addresses/Account | PASS | Status, historical totals, returns, address approval, account routes, and notifications are covered by targeted tests. | Smoke customer order/account pages. |
| Homepage/Search/Responsive | PASS | 4.22B browser QA plus Homepage/Search tests; Bootstrap pagination remains configured. | Production visual spot check still required. |
| Security/System Health | PASS | Security tests cover headers/access/secret display; System Health displays configured/missing statuses, not secret values. | Verify production env values and heartbeat. |
| Scheduler | PASS SOURCE AUDIT | `schedule:list` shows five commands with expected cadences and no duplicates. | Runtime heartbeat observation is MANUAL REQUIRED. |
| Print | PASS STATIC AUDIT | Order documents hide print actions in print media; purchase print is standalone without admin chrome. | A4 browser print preview is MANUAL REQUIRED. |
| Release packaging | PASS WITH POLISH | Package script excludes env/runtime/Git/test data and now writes a non-secret `release-manifest.json`. | Create ZIP only after user approval. |

Remaining MANUAL REQUIRED items before final live acceptance:

- Razorpay Test Mode browser transaction and webhook delivery.
- Print preview for invoice, packing slip, picking slip, and purchase print.
- Scheduler heartbeat/runtime observation.
- Backup/restore execution.
- Full production data smoke test.

## Milestone 4.22D Live Acceptance Hotfix Batch 1

No deployment, ZIP, production SQL, migration, or print-invoice change was performed.

| Area | Scenario | Result | Evidence | Manual Requirement |
| --- | --- | --- | --- | --- |
| Search/Header | Autocomplete groups product/category/brand suggestions and mobile search submit remains icon-sized. | PASS | `Search` filter covers endpoint/markup/asset regressions. | Browser-check autocomplete dropdown position at 360px, 390px, 768px, desktop. |
| Customer Account | Shop appears first, account nav is horizontally scrollable with active item metadata and notice-strip placeholder. | PASS | `Customer` filter covers account nav shell and entitled cashback visibility. | Browser-check gentle auto-scroll and pause-on-interaction behavior. |
| Entitlements | Premium badge renders only for premium customers; standard customers do not see a fake badge; cashback surfaces stay entitlement-gated. | PASS | `Customer` and `Cashback` filters passed. | Smoke one premium and one standard customer in UAT. |
| Dashboard | Customer Credit card includes Wishlist shortcut; cashback shortcut/card hidden when disabled. | PASS | `Customer`, `CustomerCredit`, `Wishlist`, `Cashback` filters passed. | Visual spot check only. |
| Addresses/Returns | Customer-facing statuses use consistent compact badges and compact action/back buttons. | PASS | `Address` and `Return` filters passed. | Mobile visual check for wrapping. |
| Orders | Customer order list/detail use compact status badges, readable timeline, MRP/GK price/GST/customer-credit breakdown. | PASS | `Order` filter covers updated order detail assertions. | Browser-check long product names and timeline wrapping. |
| Order Item Brand | Historical brand name on old order items. | NOT CHANGED | `order_items` does not store a brand snapshot; showing current catalog brand would be historically unsafe. | Add an additive historical brand snapshot in a future scoped migration if required. |
