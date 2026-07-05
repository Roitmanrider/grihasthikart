# Post-Deployment Tests

Run these checks immediately after upload, migration/import, storage setup, and cache rebuild.

## Public Storefront

- Homepage loads without debug errors.
- Product listing loads.
- Product detail loads and shows images.
- Category and brand links work.
- Invalid route returns a normal production error page, not a debug stack trace.

## Cart And Checkout

- Add product variant to cart.
- Update cart quantity.
- Remove cart item.
- Checkout with COD.
- Confirm order is created.
- Confirm inventory is deducted.
- Confirm order success page loads.

## Customer Account

- Customer login/register flow reaches the expected MVP state.
- Mobile OTP/local-dev warning is understood before launch.
- Customer dashboard loads.
- Customer order history loads.

## Admin

- Admin login works for emails configured in `GRIHASTHIKART_ADMIN_EMAILS`.
- Non-admin users cannot access admin pages.
- Admin dashboard loads.
- Admin order view loads.
- Inventory screens load.
- Coupon screen loads and coupon apply still works.
- Cashback page loads.
- GST/tax report loads.
- Payment settings page loads.
- QR payment image is visible when configured.

## Storage And Security

- Category images are visible.
- Brand logos/banners are visible.
- Product and variant images are visible.
- Payment proof uploads can be opened by admin.
- `.env` cannot be downloaded.
- `.git`, `vendor`, `storage/logs`, `app`, `database`, and `routes` are not browsable.
- `APP_DEBUG=false` is confirmed from `.env` and browser behavior.
