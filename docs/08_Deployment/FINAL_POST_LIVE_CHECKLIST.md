# Final Post-Live Checklist

Run this checklist immediately after Hostinger upload and cache setup.

## Security

- `APP_ENV=production`.
- `APP_DEBUG=false`.
- `.env` cannot be downloaded.
- `.git`, `app`, `config`, `database`, `routes`, `storage/logs`, and `vendor` are not publicly browsable.
- Admin login works only for authorized users.
- Admin credentials are secured.

## Public Store

- Homepage loads.
- Product listing loads.
- Product detail loads.
- Category and brand navigation work.
- Product, category, and brand images are visible.
- Invalid route does not show a debug stack trace.

## Cart And Checkout

- Add to cart works.
- Cart quantity update works.
- Cart remove works.
- COD checkout works.
- Order is created.
- Inventory is deducted.
- Order success page loads.

## Customer Account

- Customer login/account flow works at the approved MVP level.
- Customer dashboard loads.
- Customer order history loads.
- Email/SMS/OTP placeholder behavior is understood before public launch.

## Admin Operations

- Admin dashboard loads.
- Order index and order detail load.
- Payment admin screens load.
- QR payment settings load.
- Coupon management loads.
- Cashback page loads.
- GST/tax report loads.
- Inventory screens load.

## Payments

- COD is enabled or disabled as intended.
- QR settings show the correct UPI/display details when enabled.
- QR image is visible when configured.
- Razorpay live remains disabled unless separately approved.
- Payment secrets are not visible on storefront pages.

## Final Sign-Off

- Backup files remain available.
- Database backup remains available.
- Release ZIP and timestamp are recorded.
- Any issue found during launch is documented with the exact step and resolution.
