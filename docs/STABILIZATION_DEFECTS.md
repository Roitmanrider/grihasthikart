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
