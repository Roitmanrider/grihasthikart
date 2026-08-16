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
