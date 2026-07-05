# Production Environment Checklist

Create the real `.env` manually on Hostinger. Never upload a local `.env` and never commit production secrets.

## Required App Values

- `APP_NAME=GrihasthiKart`
- `APP_ENV=production`
- `APP_KEY=base64:...`
- `APP_DEBUG=false`
- `APP_URL=https://your-production-domain`
- `APP_LOCALE=en`
- `APP_FALLBACK_LOCALE=en`

## Database

- `DB_CONNECTION=mysql`
- `DB_HOST` from Hostinger
- `DB_PORT=3306`
- `DB_DATABASE` from Hostinger
- `DB_USERNAME` from Hostinger
- `DB_PASSWORD` from Hostinger

## Sessions, Cache, Queue, Files

- `SESSION_DRIVER=database`
- `SESSION_SECURE_COOKIE=true` when HTTPS is active
- `CACHE_STORE=database`
- `QUEUE_CONNECTION=database` for queued jobs, or `sync` only if background processing is intentionally disabled
- `FILESYSTEM_DISK=public`

## Mail

- `MAIL_MAILER=smtp`
- `MAIL_HOST=smtp.hostinger.com` or the approved mail host
- `MAIL_PORT=465` or the approved port
- `MAIL_SCHEME=smtps` or the approved scheme
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM_ADDRESS`
- `MAIL_FROM_NAME="${APP_NAME}"`

## Admin Access

- `GRIHASTHIKART_ADMIN_EMAILS=owner@example.com,manager@example.com`
- Leave it blank if no implicit admin email should be granted.
- Do not use placeholder admin emails in production.

## Payment Settings

Payment method state is stored in Admin > Settings > Payments. Keep these rules:

- COD can be enabled for MVP launch.
- QR payment requires UPI ID, display name, and QR image.
- Razorpay is placeholder-ready only; do not enable live Razorpay until the live API integration is implemented and verified.
- Do not expose Razorpay secrets on frontend pages.

## Security Checks

- `APP_DEBUG=false`
- Real `.env` is not in Git.
- `.env` cannot be downloaded from the browser.
- Admin routes require login and authorization.
- `storage` and `bootstrap/cache` are writable by PHP.
- File permissions are not globally writable.
- Old Flutter files are backed up before replacement.
