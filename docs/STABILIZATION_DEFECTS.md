# Milestone 4.22A Stabilization Defect Log

## Fixed Defects

| ID | Module | Severity | Description | Root Cause | Fix | Automated Test | Manual Verification Required | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| GK-422A-001 | Pagination UI | P2 | Paginated catalog/admin/customer pages could render Laravel's default Tailwind pagination markup, including oversized SVG arrow classes, inside the Bootstrap Blade application. | No global paginator view was configured, so `links()` used the framework default instead of Bootstrap markup. | Added `Paginator::useBootstrapFive()` during application boot. | Extended catalog pagination regression to assert Bootstrap `pagination`/`page-item` markup, query preservation, and absence of `w-5 h-5` Tailwind arrow classes. | Yes, confirm visual pagination size on mobile and desktop pages. | FIXED |
| GK-422B-001 | Admin responsive layout | P2 | Admin pages with dense tables could create page-level horizontal overflow on mobile/tablet/1024px viewports. | The admin shell combined a fixed-width sidebar with a flex content area that could not shrink cleanly around table-heavy pages. | Made the admin shell responsive, allowed the content column to shrink, contained page-level x-overflow, and kept dense tables inside `.table-responsive`. | Added an admin layout regression assertion for `admin-shell`, `admin-sidebar`, `admin-content`, `overflow-x: hidden`, and table responsiveness. | Browser retest completed at 360px, 1024px, and 1366px on key admin pages. | FIXED |
| GK-422B-002 | Customer account responsive headers | P2 | Customer account and cashback headers could overflow on narrow screens when the entitlement badge sat inline with heading content. | Heading and badge were rendered as one inline heading row, leaving the badge no clean wrap point. | Split heading and badge into a wrapping flex row and allowed adjacent text containers to shrink/wrap. | Extended customer account overview coverage to assert the responsive wrapping classes. | Browser retest completed at 360px, 390px, 768px, and 1366px. | FIXED |
| GK-422B-003 | Storefront footer tablet layout | P2 | Shared storefront footer could expose off-canvas footer columns around the tablet breakpoint. | The 991px breakpoint kept four `minmax(220px, 1fr)` footer columns inside a horizontal scroller. | Changed the footer tablet breakpoint to two shrinkable columns and constrained storefront horizontal overflow. | Covered indirectly by browser QA and CSS diff check. | Browser retest completed on customer account pages; broader storefront footer spot pass completed. | FIXED |

## Findings With No Code Change

| ID | Module | Severity | Description | Outcome | Manual Verification Required | Status |
| --- | --- | --- | --- | --- | --- | --- |
| GK-422A-002 | Cross-module workflows | P0/P1 | Requested targeted automated filters for checkout, payments, credit, coupons, inventory, order/return, customer, admin, security, and catalog flows. | All targeted filters passed after GK-422A-001. No additional P0/P1 defects reproduced in automated coverage. | Yes, complete real-browser workflow QA remains required. | VERIFIED BY TESTS |
| GK-422A-003 | Responsive tables | P2 | Static scan found many high-use Blade tables across admin/customer/account/report pages. Most are already wrapped or are document/print tables, but viewport behavior was not browser-verified. | No safe code-wide table rewrite was made in this milestone. | Yes, verify 360px, 390px, 768px, desktop. | NOT TESTED |
| GK-422A-004 | JS console behavior | P2 | Autocomplete, slider, Quick View, cart polling, and Razorpay disabled/cancel flows require live browser console verification. | Build/test coverage passed; no browser console was opened in this milestone. | Yes. | NOT TESTED |
| GK-422A-005 | Deep query profiling | P2 | Homepage/search/customer order/admin order/purchase/replenishment paths were covered by tests, but dedicated query-count profiling was not performed. | No obvious static or test failure found. | Optional query profiler pass. | NOT TESTED |
| GK-422B-004 | Razorpay browser/provider flow | P1/P2 | Razorpay success/cancel/provider callback behavior requires credentials and a payable test cart in a browser. | Not exercised in this pass. | UAT with Razorpay test credentials remains required. | NOT TESTED |
| GK-422B-005 | Print media layouts | P2 | Invoice, packing slip, picking slip, and purchase print views require browser print-media verification. | No order/purchase print fixture was created during this browser pass. | Verify print dialog/page breaks with production-like documents. | NOT TESTED |
| GK-422B-006 | Scheduler runtime observation | P2 | Pending reminders, expiry, cleanup, and operational scheduler behavior require runtime observation. | No scheduler was started or deployed in this pass. | Observe scheduled commands in production-like environment. | NOT TESTED |

## Deferred Items

These are intentionally outside Milestone 4.22A implementation scope unless promoted by a reproduced P0/P1 defect.

| Item | Reason Deferred |
| --- | --- |
| Deep visual polish | Requires separate design/viewport review cycle. |
| Image conversion/resizing pipeline | Not a stabilization defect reproduced in this pass. |
| PWA/TWA work | Explicitly deferred from prior milestones. |
| Razorpay live-mode production validation | Requires live credentials/production-like environment. |
| Major analytics/search-engine upgrades | New feature scope, not defect resolution. |
| Full ERP/test-data reset | Explicitly postponed until final MVP testing. |

## Milestone 4.22C Release Candidate Findings

| ID | Module | Severity | Description | Outcome | Manual Verification Required | Status |
| --- | --- | --- | --- | --- | --- | --- |
| GK-422C-001 | Release packaging | P3 | Release script did not emit a non-secret manifest with commit/timestamp/migration list. | Added `release-manifest.json` generation to package staging. No ZIP was created. | Confirm manifest contents when package is eventually created. | FIXED |
| GK-422C-002 | Razorpay provider E2E | P1/P2 | Real Test Mode success/failure/cancel/retry/webhook race still requires provider credentials and browser transaction. | Static code/tests pass; not claimed as real provider pass. | Yes, run the manual Razorpay Test Mode script. | MANUAL REQUIRED |
| GK-422C-003 | Print media | P2 | Invoice, packing slip, picking slip, and purchase print need A4 preview validation with representative data. | Static audit found no obvious chrome/sidebar blocker. | Yes, run browser print preview checks. | MANUAL REQUIRED |
| GK-422C-004 | Scheduler runtime | P2 | Scheduler registration is clean, but production heartbeat advancement requires runtime observation. | Source/schedule audit passed. | Yes, verify System Health heartbeat after deployment. | MANUAL REQUIRED |
| GK-422C-005 | Production backup/restore | P1 | Backup/restore cannot be executed from this non-deployment milestone. | Added backup gate to release checklist. | Yes, perform backup before release deployment. | MANUAL REQUIRED |

Release blocker summary at 4.22C audit time:

- P0 blockers: 0
- P1 blockers: 0 automated/static blockers; Razorpay provider E2E and backup gate remain manual pre-release requirements.
- P2 blockers: 0 code blockers; print/scheduler/responsive production smoke remain manual validation items.

## Milestone 4.22D Live Acceptance Hotfix Batch 1

| ID | Module | Severity | Description | Root Cause | Fix | Automated Test | Manual Verification Required | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| GK-422D-001 | Search/Header | P2 | Header autocomplete lacked grouped customer-facing metadata and mobile search submit could consume too much width. | Autocomplete rendered a flat list and mobile search button sizing was not explicitly constrained. | Grouped suggestions by product/category/brand, constrained suggestion panel and mobile icon button sizing. | Extended `SearchCatalogDiscoveryTest`. | Verify dropdown and mobile button in real browser at 360px/390px/tablet/desktop. | FIXED |
| GK-422D-002 | Customer Account Navigation | P2 | Account navigation did not prioritize Shop first and needed a mobile-friendly horizontal scroll path. | Account nav was a wrapping link set without an overflow rail or active-item scroll behavior. | Added Shop-first account rail, active metadata, notice-strip placeholder, and lightweight vanilla JS auto-scroll that pauses on user interaction/reduced motion. | Extended `CustomerAccountTest`. | Verify auto-scroll timing and pause behavior in a browser. | FIXED |
| GK-422D-003 | Entitlement Badges/Cashback | P2 | Standard customers could see non-actionable badge language, and cashback surfaces needed to stay hidden unless enabled. | Badge copy did not distinguish premium-only display from standard state; dashboard/nav needed entitlement consistency. | Added premium-only badge component and kept cashback nav/dashboard visibility conditional on `cashback_enabled`. | Updated `CustomerEntitlementSessionSecurityTest`, `CustomerAccountTest`, `Cashback` filter. | Smoke standard and premium accounts. | FIXED |
| GK-422D-004 | Customer Orders | P2 | Order detail needed clearer timeline, status badges, item financial breakdown, included GST, and Customer Credit visibility. | Older detail copy compressed item economics and status display into less scannable text. | Added compact status badges, timeline cards, per-item Unit MRP/MRP Total/GK price/GK Merchandise/discount/GST text, and always-visible Customer Credit Used row. | Updated `OrderFinancialDisplayManagementTest`; `Order` filter passed. | Check long item names and timeline wrapping in browser. | FIXED |
| GK-422D-005 | Account Status/Actions | P2 | Address, return, profile, credit, cashback, and security pages needed consistent compact headers/status badges/back actions. | Pages used locally styled headings/actions and mixed badge classes. | Added shared customer page header/status badge components and compact action styles across customer account pages. | Updated `CustomerAccountTest` and `ReturnRequestManagementTest`; `Address`, `Return`, `CustomerCredit`, `Notification` filters passed. | Mobile account visual pass. | FIXED |

## Milestone 4.22D Findings With No Code Change

| ID | Module | Severity | Description | Outcome | Manual Verification Required | Status |
| --- | --- | --- | --- | --- | --- | --- |
| GK-422D-006 | Historical Order Brand | P2 | Live request asked for brand display in order item detail. | Existing `order_items` schema has product/variant/SKU snapshots but no brand snapshot. Current catalog brand was not shown as historical order data because it could become inaccurate after catalog edits. | Decide whether to add an additive brand snapshot field in a future scoped migration. | DEFERRED |
| GK-422D-007 | Print Invoice | P2 | Print invoice behavior must remain untouched. | No print invoice Blade/service/controller changes were made in this batch. | Browser print preview remains a manual release gate from 4.22C. | VERIFIED STATICALLY |

## Milestone 4.22E Live Acceptance Hotfix Batch 2

| ID | Module | Severity | Description | Root Cause | Fix | Automated Test | Manual Verification Required | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| GK-422E-001 | Admin Customers | P1 | Admin could view customer addresses but could not create/edit addresses for offline/phone orders. | Admin customer controller exposed approval toggle only. | Added authorized admin create/update/default address routes and forms; admin-created/admin-edited addresses can remain approved. | `Address` filter. | Create/edit/default/reject one address in admin. | FIXED |
| GK-422E-002 | Cart Activity Monitor | P2 | Oldest or soonest-expiring rows appeared first by default. | Default sort was `expires_soonest`. | Changed default to `most_recently_active` and kept explicit special sorts. | `Cart` filter. | Check live-like pending order list ordering. | FIXED |
| GK-422E-003 | Inventory Reservations | P1 | Active customer carts did not affect Inventory Reserved Quantity. | Pending cart activity tracked holds but did not reconcile `inventories.reserved_quantity`. | Added referenced inventory reservation/release movements for active pending carts and checkout release-before-deduct behavior. | `Cart`, `Inventory`, `Order` filters. | Add/remove cart item and inspect Inventory reserved quantity. | FIXED |
| GK-422E-004 | Admin Buttons/Tables | P2 | Admin buttons and table rows were visually inconsistent and oversized in places. | Button sizing was mostly page-local Bootstrap defaults. | Added scoped `.admin-shell` compact button/table spacing rules. | Source/CSS plus full suite. | Browser spot check representative admin pages. | FIXED |
| GK-422E-005 | Admin Order Alerts | P2 | Admin order detail could show duplicate success alerts. | Global admin flash partial and local order-detail success block both rendered the same session key. | Removed local success block from order detail; global flash remains authoritative. | `Order` filter. | Trigger admin status update. | FIXED |
| GK-422E-006 | Checkout Mobile | P2 | Checkout sections needed safer mobile spacing and controls. | Dense form controls wrapped unevenly on small viewports. | Added presentation-only checkout hooks/CSS for headings, address chip rail, payment cards, and Place Order button. | Existing checkout/order tests and full suite. | Browser check 360/390/430/768. | FIXED |
| GK-422E-007 | Catalog Filters | P2 | Large filters appeared before product content, especially on mobile. | Filter form was a large always-visible card. | Added compact sticky filter/sort shell, active chips, and mobile off-canvas filter panel while preserving query names. | `Catalog` filter. | Browser check sticky and off-canvas behavior. | FIXED |
| GK-422E-008 | Scheduler Heartbeat | P1 | System Health could show no scheduler heartbeat when cache did not retain the timestamp. | Heartbeat was cache-only. | Scheduler command now writes cache plus persistent `storage/framework/scheduler-heartbeat.json`; System Health reads file fallback. | `SystemHealth` filter. | Confirm cron updates heartbeat after deployment. | FIXED |

## Milestone 4.22E Findings With No Code Change

| ID | Module | Severity | Description | Outcome | Manual Verification Required | Status |
| --- | --- | --- | --- | --- | --- | --- |
| GK-422E-009 | Razorpay Provider E2E | P1/P2 | Razorpay Test Mode could not be run without keys. | Left as MANUAL REQUIRED / NOT TESTED. | Yes. | MANUAL REQUIRED |

## Milestone 4.24A Daily Offers, Store Context & Live Acceptance Corrections

| ID | Module | Severity | Description | Root Cause | Fix | Automated Test | Manual Verification Required | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| GK-424A-001 | Store Navigation | P2 | Store CRUD existed but was not exposed as a Super Admin Operations entry. | Sidebar lacked a Stores link. | Added Super Admin-only Stores link and kept Store Manager direct access denied by controller authorization. | `Store` filter. | Browser sidebar check. | FIXED |
| GK-424A-002 | Daily Offers Authorization | P1 | Store Managers could mutate Daily Offers through the old manage gate. | Daily Offer view and mutation permissions used one gate. | Split `view-daily-offers` from `manage-daily-offers`; Store Manager is assigned-store view-only, mutations remain Super Admin/admin-email only. | `DailyOffer` filter. | Direct URL smoke. | FIXED |
| GK-424A-003 | Daily Offers Store Scope | P1 | Daily Offer listing/allocation/current-offer lookups could cross store context. | Repository/service queries did not consistently filter `stock_location_id`. | Scoped admin listing, current offers, duplicate checks, allocation math, cart offer lookup, and customer store lookup to the selected store where applicable. | `DailyOffer`, `Store`, `Catalog`, `Homepage` filters. | Multi-store UAT with two stores. | FIXED |
| GK-424A-004 | Expired Offer Mutation | P2 | Expired Daily Offers could be edited like active records. | Lifecycle state was displayed but not enforced on edit/update/delete. | Blocked expired edit/update/delete and added Duplicate as New Offer path. | `DailyOffer` filter. | Browser-check expired detail/list actions. | FIXED |
| GK-424A-005 | Daily Offer Admin Validation | P2 | Create/update did not enforce all production scheduling, display order, and max quantity requirements. | Earlier MVP validation allowed nullable dates/order/max quantity. | Required bounded dates, display order >= 1, max quantity/order >= 1, max <= allocation/product max, same-store overlapping display-order uniqueness, and selected store. | `DailyOffer` filter. | Admin invalid-form smoke. | FIXED |
| GK-424A-006 | Storefront Daily Offer Presentation | P2 | Daily Offer cards used older compact metadata and hardcoded section subtitle fallback. | Section defaults had hardcoded timezone copy; custom badge and discount were conflated. | Added editable section message/autoslide config via homepage section metadata, MRP-based discount badge, separate custom badge, improved card metadata, carousel controls, and day/hour/min countdown. | `DailyOffer`, `Homepage`, `Catalog` filters. | Real-browser responsive carousel QA. | FIXED |

## Milestone 4.24B Staff Operations Portal

| ID | Module | Severity | Description | Root Cause | Fix | Automated Test | Manual Verification Required | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| GK-424B-001 | Staff Model | P1 | Employees needed multiple operational roles without separate accounts. | Legacy `users.role` allowed one role only. | Added additive staff role/permission JSON fields and centralized permission service. | `Staff` filter. | Staff form smoke. | FIXED |
| GK-424B-002 | Staff Portal | P1 | Operational staff needed a common portal separate from Super Admin. | No `/staff` route boundary existed. | Added Staff login/dashboard/queues/deliveries/approvals/notifications with route authorization. | `Staff` filter. | Browser smoke. | FIXED |
| GK-424B-003 | Delivery Evidence | P1 | Delivery status needed event-based GPS and audit history. | Existing orders had no compact delivery attempt/event model. | Added delivery attempts/events and optional customer address GPS fields. | `Staff` filter. | Device GPS smoke. | FIXED |
| GK-424B-004 | Internal OTP | P1 | Delivery needed OTP without paid SMS and without staff seeing customer OTP. | No internal delivery OTP lifecycle existed. | Added hashed/encrypted temporary OTP credentials, customer display surfaces, verification, invalidation, and cleanup command. | `Staff` filter. | Customer account smoke. | FIXED |
| GK-424B-005 | Maker-Checker | P1 | Sensitive return/override requests needed independent approval. | No reusable approval inbox existed. | Added staff approval requests and self-approval block with Super Admin-capable fallback. | `Staff` filter. | Approval inbox smoke. | FIXED |

## Milestone 4.24C Admin Store Context, Stores Navigation & Staff Management Access

| ID | Module | Severity | Description | Root Cause | Fix | Automated Test | Manual Verification Required | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| GK-424C-001 | Store Navigation | P2 | Existing configured Super Admin/admin-email users could access `/admin/stores` directly but did not consistently see Stores navigation. | Store navigation used `isSuperAdmin()` while the route gate also allowed configured admin emails. | Added `User::isAdminPrincipal()` and `canManageStores()`, then used the helper for Stores sidebar and controller authorization. | `AdminStoreContextNavigation`, `Store`, full suite. | Browser-check configured admin email sidebar. | FIXED |
| GK-424C-002 | Store Context Selector | P1 | Daily Offers told admins to select a store from the topbar while configured admin principals did not see the topbar selector. | Store Context UI and service checks used `isSuperAdmin()` instead of the same admin principal rule as admin authorization. | Added `canSwitchStoreContext()`, exposed the selector to admin principals, restricted selector options to active stores, and cleared stale inactive selections. | `AdminStoreContextNavigation`, `DailyOffer`, `Dashboard`, full suite. | Verify Store Context persistence across admin pages. | FIXED |
| GK-424C-003 | Store Manager Boundary | P1 | Store Managers needed assigned-store context without gaining arbitrary store switching or Stores CRUD. | Selector and CRUD visibility were not documented/tested against the new admin principal helper. | Kept Store Managers on a fixed assigned-store label, hid Stores CRUD/nav, and preserved direct 403 protection. | `AdminStoreContextNavigation`, `Store`, full suite. | Direct URL smoke as Store Manager. | FIXED |
| GK-424C-004 | Staff Management Access | P1 | Staff employee provisioning was not discoverable from Admin and create flow was missing from the management surface. | Admin staff management focused on editing existing users and the sidebar did not expose Staff / Employees. | Added Staff / Employees navigation plus an Add Staff flow that provisions `users` with store, active flag, role bundles, explicit allow permissions, and denied overrides. | `AdminStoreContextNavigation`, `Staff`, full suite. | Create/edit one employee in Admin UI. | FIXED |
| GK-424C-005 | Staff Portal Admission | P1 | `/staff` behavior needed to distinguish guest login from authorized operational staff admission and avoid auto-admitting Super Admins as staff. | Staff routes relied on the generic auth middleware and operational staff detection treated Super Admin as staff automatically. | Let the staff controller redirect guests to Staff login, require `staff_active` plus operational staff roles, and keep Super Admin staff access opt-in through `staff_roles`. | `AdminStoreContextNavigation`, `Staff`, full suite. | Browser staff login/logout smoke. | FIXED |

Fresh live UAT mapping: SET-01 ST-01-02,04,05,06,07,08,09,10,11,12 and SET-02 ST-02-01 through ST-02-12 are ready for fresh live UAT, not claimed as live-passed.
