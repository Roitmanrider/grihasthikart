# Staff Operations Portal

Milestone 4.24B adds a dedicated staff portal inside the same Laravel application. It does not duplicate the project, vendor folder, or database.

## Portal Boundaries

- Customer storefront: `grihasthikart.in`
- Super Admin: `admin.grihasthikart.in`
- Staff: `staff.grihasthikart.in`

Current transition keeps path-based access working:

- `/admin` remains available for current production Super Admin access.
- `/staff` provides the staff portal before subdomain DNS is enabled.
- `config/grihasthikart.php` contains `GK_CUSTOMER_HOST`, `GK_ADMIN_HOST`, `GK_STAFF_HOST`, and `GK_PORTAL_ENFORCE_HOSTS` placeholders.

Subdomains are not treated as security. Staff portal routes require authentication, active staff status, reusable permissions, and store scope checks.

## Employee Model

Staff continues to use `users`.

Additive fields:

- `staff_roles` JSON
- `additional_permissions` JSON
- `denied_permissions` JSON
- `staff_active`
- `staff_approved_at`
- `staff_approved_by`

The legacy `role` column remains for compatibility with current Super Admin/admin flows.

## Role Bundles

- `STORE_MANAGER`
- `INVENTORY_STAFF`
- `PICKER_PACKER`
- `DELIVERY_AGENT`
- `CART_FOLLOW_UP_EMPLOYEE`
- Preserved: `SUPER_ADMIN`

One employee may hold multiple bundles. Permission checks are centralized in `App\Domains\Staff\Services\StaffPermissionService`.

## Staff Portal Modules

The staff layout shows modules dynamically by permission:

- Dashboard
- Picking Queue
- Packing Queue
- My Deliveries
- Approvals
- Notifications

## Notifications

Staff notifications are separate from customer notifications and stored in `staff_notifications`.

Unread counts are grouped by `workstream`, so one logical notification decrements only its relevant box when read.

## Security Rules

- Store staff must have `assigned_store_id`.
- Staff cannot access `/admin` unless they also have the existing admin authorization.
- Store-scoped actions reject cross-store IDs.
- Task assignment requires the corresponding assign permission; task execution requires the corresponding start/complete permission.
- Delivery assignment is separate from delivery permission.
- Sensitive approval self-approval is blocked by maker-checker logic.
- Explicit denied permissions override explicit allows and role grants.
- Customer OTP values are never rendered in staff or ordinary admin screens.

## Future Mobile Readiness

The portal controllers call reusable services:

- `OrderStaffTaskService`
- `DeliveryWorkflowService`
- `StaffNotificationService`
- `StaffPermissionService`

Future staff/mobile API controllers should reuse those services rather than reimplement workflow logic in API actions.

## Milestone 4.24C Admin Employee Access

- Super Admin/admin-email users manage employees from Admin > Operations > Staff / Employees.
- Staff creation provisions an existing `users` account with name, email, temporary password, assigned store, active flag, operational role bundles, explicit allow permissions, and denied permission overrides.
- Non-Super Admin operational roles require `assigned_store_id`.
- Denied permissions continue to override role grants and explicit allows.
- Super Admin status alone does not grant Staff Portal admission. A Super Admin must also be intentionally configured as active operational staff through `staff_roles` before `/staff` access is allowed.
- Unauthenticated `/staff` requests redirect to the Staff login page.
- Active operational staff may enter `/staff`; inactive staff, plain users, and Super Admin users without operational staff roles receive 403.
- No migration is required for this access cleanup.

Fresh live UAT status: SET-02 staff management and staff portal access scenarios ST-02-01 through ST-02-12 are ready for verification.
