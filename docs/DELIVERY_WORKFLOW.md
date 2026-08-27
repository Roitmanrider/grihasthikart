# Delivery Workflow

Milestone 4.24B introduces compact operational delivery records without changing checkout or payment architecture.

## Task Model

`order_staff_assignments` stores reusable task assignments:

- `PICKING`
- `PACKING`
- `DELIVERY`

This avoids role-specific order columns such as `picker_id`, `packer_id`, or `delivery_agent_id`.

## Picking And Packing

Authorized employees can start and complete assigned tasks.

The same employee may complete picking and packing if they have the required permissions. No maker-checker is required for ordinary pick/pack progression.

## Delivery Attempts

`delivery_attempts` stores attempt state and agent assignment. Delivery events are append-only in `delivery_events`.

Supported event/status vocabulary includes:

- `OUT_FOR_DELIVERY`
- `DELIVERED`
- `DELIVERY_FAILED_BY_AGENT`
- `CUSTOMER_UNAVAILABLE`
- `RESCHEDULE_REQUESTED`
- `RETURN_TO_STORE_PENDING`
- `RETURNED_TO_STORE_CONFIRMED`
- `RETURNED_TO_STORE_REJECTED`
- `DELIVERED_OTP_OVERRIDE_APPROVED`

## Internal Delivery OTP

When a delivery attempt starts:

- A secure random 6-digit OTP is generated.
- `delivery_otps.otp_hash` stores the verification hash.
- `delivery_otps.otp_ciphertext` stores a temporary encrypted copy so the customer can view the active OTP in account notifications/order detail.
- Customer notification title/message/data never stores the six-digit plaintext OTP permanently; notification data stores only the temporary delivery OTP credential ID.
- Staff never sees the OTP value.
- Customer display is authorized through the active OTP lifecycle: own order, active delivery attempt, unused, not invalidated, and unexpired.
- Verification invalidates the OTP and records an audit event.
- Five invalid OTP attempts invalidate the credential and require delivery override or reschedule recovery. The response never gives partial-match feedback.

Cleanup:

- `delivery-otps:cleanup` deletes only used, invalidated, or already-expired credential rows after 7 days.
- Active credentials are not deleted solely because `created_at` is old.
- Permanent `delivery_events` remain.

## GPS Evidence

GPS is event-based only. There is no continuous staff tracking.

Delivery action forms disclose: `Location will be recorded with this delivery update.`

GPS is optional:

- Valid OTP + GPS near customer pin: delivered, strong evidence.
- Valid OTP + GPS unavailable: delivered with unavailable evidence.
- Valid OTP + GPS far: delivered and flagged for review.

Customer address pins are optional:

- `latitude`
- `longitude`
- `geofence_radius_meters`

Customer edits to location pin are treated as significant address changes and return the address to pending approval.

## Maker-Checker

Sensitive requests use `staff_approval_requests`.

Examples:

- Returned to Store
- Delivery OTP Override

The requester cannot approve their own request, even if they hold both Delivery Agent and Store Manager roles. If a store has no independent checker, Super Admin can approve.

Returned-to-store approval means only that the physical package was received back by store. It does not return merchandise to sellable inventory.

Valid OTP delivery does not require approval. OTP override delivery records `DELIVERED_OTP_OVERRIDE_APPROVED`; it does not pretend that an OTP was verified.
