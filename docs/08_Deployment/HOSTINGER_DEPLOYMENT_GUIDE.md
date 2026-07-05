# Hostinger Deployment Guide

This guide prepares GrihasthiKart for Hostinger Premium Web Hosting using a File Manager/manual upload workflow. It does not deploy automatically and does not add new application features.

## Deployment Readiness Review

CRITICAL:

- No real secrets should be committed. Use `.env.production.example` as a template only.
- `APP_DEBUG` must be `false` in production.
- The public web root must not expose `.env`, `vendor`, `storage`, `app`, `database`, `routes`, or `config`.

HIGH:

- Hostinger document root should point to Laravel `public` whenever possible.
- `storage/app/public` must be connected to `public/storage` for product, category, brand, QR, and payment proof images.
- Production database backup must exist before running migrations or importing SQL.
- Admin access depends on `GRIHASTHIKART_ADMIN_EMAILS`; do not leave unintended privileged emails configured.

MEDIUM:

- SSH is strongly preferred for migrations, cache commands, and storage links. If SSH is unavailable, use phpMyAdmin and File Manager workarounds.
- Queue processing may need a Hostinger cron if background jobs become important.

LOW:

- Future automation can create release ZIPs and checksums, but manual packaging is acceptable for this milestone.

## Pre-Deployment Local Checklist

- Confirm `git status --short` is clean.
- Run the MVP verification tests.
- Run Laravel Pint in test mode.
- Run `npm run build`.
- Confirm `composer.lock` exists and dependencies are installed.
- Review `.env.production.example` and prepare a real server `.env`.
- Back up the current Hostinger files before replacing the old Flutter web output.
- Back up the production database if one already exists.
- Confirm `storage/app/public` contains required uploaded media.
- Confirm `public/build` exists after Vite build.

## Local Build Commands

Run these before packaging a release:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```

Do not upload `node_modules`. Upload `vendor` only when Composer cannot be run on Hostinger.

## Production Commands When SSH Is Available

Run from the Laravel project directory after uploading files and creating `.env`:

```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Use `php artisan key:generate` only when `APP_KEY` is missing. Run seeders only for approved master data; do not seed demo/test data into real production.

## Hostinger Folder Structure

Preferred option:

- Keep the Laravel project outside `public_html`, for example `domains/your-domain/grihasthikart`.
- Copy or map the contents of Laravel `public` into `public_html`.
- Update `public_html/index.php` paths to load `../grihasthikart/vendor/autoload.php` and `../grihasthikart/bootstrap/app.php`.

Fallback option:

- Upload the Laravel project beside `public_html` if Hostinger allows another folder.
- Put only the contents of Laravel `public` inside `public_html`.
- Update `index.php` paths to point to the real project folder.

Last-resort option:

- If Hostinger only permits files inside `public_html`, keep Laravel `public` as the only exposed web root if possible.
- Do not expose `.env`, `vendor`, `storage`, `app`, `database`, `routes`, `config`, `tests`, or `.git`.
- Add server rules to block sensitive paths if a nonstandard structure is unavoidable.

## Database Deployment

With SSH:

- Create the MySQL database and user in Hostinger.
- Put the credentials in the production `.env`.
- Run `php artisan migrate --force`.
- Run `php artisan db:seed --force` only for approved master data.

Without SSH:

- Create the MySQL database and user in Hostinger.
- Export the prepared local/staging database as SQL.
- Import the SQL through phpMyAdmin.
- Update `.env` manually using the Hostinger database host, name, user, and password.
- Keep a SQL backup before importing a replacement database.

## Storage And Images

- Uploaded media uses `storage/app/public`.
- Public URLs are served through `public/storage`.
- With SSH, run `php artisan storage:link`.
- If symlinks are unavailable, create the link using Hostinger File Manager if supported.
- If File Manager cannot create links, copy the public media into `public/storage` and document that future uploads must be synchronized until symlink support is restored.
- Keep `storage` and `bootstrap/cache` writable by PHP.
- QR payment images, product images, category images, brand logos, and payment proofs must remain on the public disk.

## Permissions

Use typical permissions:

- Folders: `755`
- Files: `644`
- Writable folders: `storage` and `bootstrap/cache`

Avoid `777`. If it is used temporarily during emergency troubleshooting, revert it after the issue is resolved.

## Cache And Build Notes

- `public/build` must be included in the release ZIP.
- Clear caches before packaging.
- Rebuild config, route, and view caches only after the production `.env` exists on the server.
- If a cached config points to the wrong database or URL, run the clear commands and cache again.
